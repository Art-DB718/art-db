<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Contact;
use App\Models\InvoiceSetting;
use App\Models\Sale;
use App\Models\SaleLineItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class CheckoutController extends Controller
{
    /**
     * POST /artworks/{slug}/buy — start Stripe Checkout for a single artwork.
     */
    public function buy(Artwork $artwork, Request $request): RedirectResponse
    {
        abort_unless($artwork->is_published, 404);
        abort_if($artwork->price_on_request || ! $artwork->price, 400, 'This work is not directly purchasable.');

        $apiKey = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $apiKey) {
            return redirect()->route('artworks.show', $artwork)
                ->with('inquiry_message', 'Online checkout is not yet enabled. Please use the inquiry form.');
        }

        $stripe = new StripeClient($apiKey);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($artwork->currency ?: 'eur'),
                    'unit_amount' => (int) round((float) $artwork->price * 100),
                    'product_data' => [
                        'name' => trim(($artwork->artist?->display_name ?? '').' — '.$artwork->title),
                        'description' => $artwork->year_created
                            ? "Year: {$artwork->year_created}".($artwork->medium?->name ? ' · '.$artwork->medium->name : '')
                            : ($artwork->medium?->name ?: null),
                        'metadata' => [
                            'artwork_id' => (string) $artwork->id,
                            'inventory_id' => (string) $artwork->inventory_id,
                        ],
                    ],
                ],
            ]],
            'success_url' => route('checkout.success', $artwork).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('artworks.show', $artwork),
            'customer_email' => $request->input('email'),
            'metadata' => [
                'artwork_id' => (string) $artwork->id,
                'inventory_id' => (string) $artwork->inventory_id,
            ],
        ]);

        return redirect()->away($session->url);
    }

    /**
     * Stripe success landing page.
     * GET /checkout/success/{slug}?session_id=...
     */
    public function success(Artwork $artwork, Request $request)
    {
        return view('public.checkout-success', [
            'artwork'    => $artwork,
            'session_id' => $request->input('session_id'),
        ]);
    }

    /**
     * Unified Stripe webhook — POST /stripe/webhook.
     * Handles both one-time artwork purchases and SaaS subscription events.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret') ?: env('STRIPE_WEBHOOK_SECRET');

        if (! $secret) {
            Log::warning('Stripe webhook hit without STRIPE_WEBHOOK_SECRET configured.');
            return response()->json(['error' => 'webhook secret not configured'], 503);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature mismatch: '.$e->getMessage());
            return response()->json(['error' => 'invalid signature'], 400);
        }

        return match ($event->type) {
            'checkout.session.completed'      => $this->handleCheckoutSessionCompleted($event->data->object),
            'customer.subscription.created',
            'customer.subscription.updated'   => $this->handleSubscriptionUpsert($event->data->object),
            'customer.subscription.deleted'   => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_succeeded'       => $this->handleInvoicePaymentSucceeded($event->data->object),
            'invoice.payment_failed'          => $this->handleInvoicePaymentFailed($event->data->object),
            default                           => response()->json(['ignored' => $event->type]),
        };
    }

    /**
     * checkout.session.completed has two flavours we care about:
     *   - mode === 'subscription' → SaaS plan upgrade landed (let Cashier sync
     *     pull the rest; we mark user active + plan immediately so the UI updates)
     *   - mode === 'payment' with artwork_id metadata → one-time art purchase →
     *     create Sale + Contact (legacy Art DB behaviour)
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        if (($session->mode ?? null) === 'subscription') {
            // Subscription checkout — actual status flip happens on
            // customer.subscription.created (fires immediately after).
            // We just ack here so Stripe doesn't retry.
            return response()->json(['ok' => true, 'mode' => 'subscription']);
        }

        // One-time artwork purchase path (existing logic).
        $artworkId = (int) ($session->metadata->artwork_id ?? 0);
        $artwork = Artwork::find($artworkId);

        if (! $artwork) {
            return response()->json(['ignored' => 'no artwork metadata']);
        }

        $email = $session->customer_details->email ?? $session->customer_email ?? null;
        $name  = $session->customer_details->name  ?? '';
        $names = preg_split('/\s+/', trim($name), 2);

        $contact = null;
        if ($email) {
            $contact = Contact::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $names[0] ?? null,
                    'last_name'  => $names[1] ?? null,
                    'source'     => 'stripe checkout',
                ],
            );
        }

        $amount = (float) ($session->amount_total / 100);
        $currency = strtoupper($session->currency ?? 'EUR');

        $sale = Sale::create([
            'buyer_contact_id' => $contact?->id,
            'sale_date'        => now()->toDateString(),
            'payment_status'   => 'paid',
            'payment_method'   => 'stripe',
            'currency'         => $currency,
            'subtotal'         => $amount,
            'total'            => $amount,
            'paid_amount'      => $amount,
            'notes'            => 'Stripe session: '.$session->id,
        ]);

        SaleLineItem::create([
            'sale_id'     => $sale->id,
            'artwork_id'  => $artwork->id,
            'quantity'    => 1,
            'unit_price'  => $amount,
            'line_total'  => $amount,
            'description' => $artwork->title,
        ]);

        $sale->recalculateTotals();

        return response()->json(['ok' => true, 'sale_id' => $sale->id]);
    }

    /**
     * customer.subscription.created / .updated → mirror Stripe state onto
     * the User row so the Filament Billing page + middleware see it without
     * a roundtrip to Stripe.
     */
    protected function handleSubscriptionUpsert($subscription)
    {
        $user = User::where('stripe_id', $subscription->customer)->first();
        if (! $user) {
            return response()->json(['ignored' => 'unknown customer']);
        }

        $priceId = $subscription->items->data[0]->price->id ?? null;
        $planKey = $this->resolveOurPlanKey($priceId);

        // Stripe statuses we map: trialing → trial, active → active, past_due
        // → past_due, canceled → cancelled, unpaid/incomplete → past_due.
        $statusMap = [
            'trialing'           => 'trial',
            'active'             => 'active',
            'past_due'           => 'past_due',
            'unpaid'             => 'past_due',
            'incomplete'         => 'past_due',
            'incomplete_expired' => 'archived',
            'canceled'           => 'cancelled',
        ];
        $status = $statusMap[$subscription->status] ?? $subscription->status;

        $user->forceFill([
            'subscription_plan'        => $planKey ?? $user->subscription_plan,
            'subscription_status'      => $status,
            'subscription_expires_at'  => $subscription->cancel_at
                ? \Carbon\Carbon::createFromTimestamp($subscription->cancel_at)
                : null,
        ])->save();

        return response()->json(['ok' => true, 'user' => $user->id, 'plan' => $planKey, 'status' => $status]);
    }

    protected function handleSubscriptionDeleted($subscription)
    {
        $user = User::where('stripe_id', $subscription->customer)->first();
        if (! $user) {
            return response()->json(['ignored' => 'unknown customer']);
        }
        $user->forceFill([
            'subscription_status'     => 'cancelled',
            'subscription_expires_at' => now(),
        ])->save();

        return response()->json(['ok' => true, 'user' => $user->id, 'status' => 'cancelled']);
    }

    protected function handleInvoicePaymentSucceeded($invoice)
    {
        $user = User::where('stripe_id', $invoice->customer)->first();
        if (! $user) {
            return response()->json(['ignored' => 'unknown customer']);
        }
        // Successful payment → unstick from past_due if we had it.
        if (in_array($user->subscription_status, ['past_due', 'trial'], true)) {
            $user->forceFill(['subscription_status' => 'active'])->save();
        }
        return response()->json(['ok' => true, 'user' => $user->id]);
    }

    protected function handleInvoicePaymentFailed($invoice)
    {
        $user = User::where('stripe_id', $invoice->customer)->first();
        if (! $user) {
            return response()->json(['ignored' => 'unknown customer']);
        }
        $user->forceFill(['subscription_status' => 'past_due'])->save();
        return response()->json(['ok' => true, 'user' => $user->id, 'status' => 'past_due']);
    }

    /**
     * Reverse lookup from a Stripe Price ID back to our internal plan slug
     * (starter / pro / studio) by scanning config('subscription.plans').
     * Returns null if no match — we'll keep whatever plan the user had.
     */
    protected function resolveOurPlanKey(?string $stripePriceId): ?string
    {
        if (! $stripePriceId) {
            return null;
        }
        foreach (config('subscription.plans', []) as $key => $plan) {
            if (($plan['stripe_price_monthly'] ?? null) === $stripePriceId
                || ($plan['stripe_price_yearly'] ?? null) === $stripePriceId) {
                return $key;
            }
        }
        return null;
    }
}
