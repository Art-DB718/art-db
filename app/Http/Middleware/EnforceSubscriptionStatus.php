<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only mode for users past their trial.
 *
 * Logged-in users with subscription_status of past_due / archived / cancelled
 * may still GET admin pages, but POST/PUT/PATCH/DELETE is blocked with a
 * billing redirect. Active and trial users pass through unchanged.
 */
class EnforceSubscriptionStatus
{
    /** Methods that we treat as write actions. */
    protected const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Trial: if expired, flip to past_due lazily on hit.
        if ($user->subscription_status === 'trial'
            && $user->trial_ends_at
            && $user->trial_ends_at->isPast()) {
            $user->forceFill([
                'subscription_status' => 'past_due',
            ])->save();
        }

        // Read-only roles only block writes. Active + trial pass everything through.
        if ($user->isReadOnly() && in_array($request->method(), self::WRITE_METHODS, true)) {
            // Always allow billing-related routes through so user can resubscribe.
            if ($request->routeIs('filament.admin.pages.billing*')
                || $request->routeIs('cashier.*')
                || str_starts_with($request->path(), 'stripe/')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription is past due. Please update billing to continue.',
                    'billing_url' => url('/admin/billing'),
                ], 402);
            }

            return redirect('/admin/billing')->with('status', 'Your subscription has expired — admin is read-only until you renew.');
        }

        return $next($request);
    }
}
