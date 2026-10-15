<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Stores and tears down the Web Push subscription a browser's pushManager
 * returns after the user grants Notification permission. Each row is scoped
 * to the authenticated user via the HasPushSubscriptions trait on the User
 * model, so a sign-out does not detach existing subscriptions (users often
 * sign in on multiple devices and expect them all to keep ringing).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'          => 'required|url|max:2000',
            'keys.p256dh'       => 'required|string|max:255',
            'keys.auth'         => 'required|string|max:255',
            'contentEncoding'   => 'nullable|string|max:50',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $subscription = $user->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? null,
        );

        return response()->json([
            'ok' => true,
            'id' => $subscription->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|url|max:2000',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }
}
