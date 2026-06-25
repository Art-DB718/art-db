<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Contact;
use App\Models\InvoiceSetting;
use App\Models\Sale;
use App\Models\SaleLineItem;
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
     * Stripe webhook — POST /stripe/webhook.
     * checkout.session.completed → create Sale record.
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

        if ($event->type !== 'checkout.session.completed') {
            return response()->json(['ignored' => $event->type]);
        }

        $session = $event->data->object;
        $artworkId = (int) ($session->metadata->artwork_id ?? 0);
        $artwork = Artwork::find($artworkId);

        if (! $artwork) {
            return response()->json(['error' => 'artwork not found'], 404);
        }

        // Upsert buyer Contact from Stripe customer details.
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

        // Create Sale.
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
}
