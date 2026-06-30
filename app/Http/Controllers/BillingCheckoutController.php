<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingCheckoutController extends Controller
{
    /**
     * Start a Stripe Checkout session for the chosen plan + billing cycle.
     * Cashier creates the Stripe customer if it doesn't exist yet.
     */
    public function subscribe(Request $request, string $plan, string $cycle)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $plans = config('subscription.plans', []);
        abort_unless(isset($plans[$plan]) && in_array($cycle, ['monthly', 'yearly'], true), 404);

        $priceId = $plans[$plan]['stripe_price_'.$cycle] ?? null;
        abort_if(blank($priceId), 422, 'Stripe Price ID for this plan is not configured.');

        $checkout = $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}&plan='.$plan,
            'cancel_url'  => route('billing.cancel'),
        ]);

        return redirect($checkout->url);
    }

    /**
     * Stripe redirects here after the user completes payment.
     * Cashier's webhook will eventually sync the subscription, but we flip
     * the local plan + status optimistically so the UI updates immediately.
     */
    public function success(Request $request)
    {
        $user = $request->user();
        $plan = $request->query('plan');

        if ($user && $plan && config("subscription.plans.$plan")) {
            $user->forceFill([
                'subscription_plan'   => $plan,
                'subscription_status' => 'active',
                'trial_ends_at'       => null,
            ])->save();
        }

        return redirect('/admin/billing')->with('status', 'Payment successful — your subscription is active.');
    }

    public function cancel(Request $request)
    {
        return redirect('/admin/billing')->with('status', 'Checkout cancelled — no charges were made.');
    }

    /**
     * Open the Stripe-hosted Customer Portal so the user can update their
     * card, view invoices, switch plans, or cancel their subscription
     * without us having to build any of that ourselves.
     *
     * Returns to /admin/billing after the user closes the portal.
     */
    public function portal(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasStripeId()) {
            return redirect('/admin/billing')
                ->with('status', 'No subscription on file — pick a plan first to manage your billing.');
        }

        return $user->redirectToBillingPortal(url('/admin/billing'));
    }
}
