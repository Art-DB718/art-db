<?php

use App\Enums\UserRole;
use App\Models\User;

// Confirms EnforceSubscriptionStatus middleware behaviour end-to-end:
// - trial users can hit admin
// - past_due / archived / cancelled users are blocked from write actions
// - billing routes stay reachable even in read-only mode

function makeUser(string $status, ?string $plan = null): User {
    return User::factory()->create([
        'role'                => UserRole::Gallery,
        'subscription_status' => $status,
        'subscription_plan'   => $plan,
        'trial_ends_at'       => $status === 'trial' ? now()->addDays(7) : null,
    ]);
}

it('lets trial users into the admin panel', function () {
    $user = makeUser('trial');
    $this->actingAs($user)
        ->get('/admin/billing')
        ->assertOk();
});

it('lets active-plan users into the admin panel', function () {
    $user = makeUser('active', 'starter');
    $this->actingAs($user)->get('/admin/billing')->assertOk();
});

it('blocks past_due users from write actions via the middleware', function () {
    $user = makeUser('past_due', 'starter');

    // GET /admin/billing must stay reachable so they can upgrade
    $this->actingAs($user)->get('/admin/billing')->assertOk();

    // Middleware-level check: past_due + POST → redirect to /admin/billing.
    // Testing directly on the middleware avoids Filament's Livewire wrapping.
    $middleware = app(\App\Http\Middleware\EnforceSubscriptionStatus::class);
    $request = \Illuminate\Http\Request::create('/admin/artworks', 'POST');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('ok'));
    expect($response->status())->toBe(302);
    expect($response->headers->get('location'))->toContain('/admin/billing');
});

it('exposes trial days-left helper on User', function () {
    $user = makeUser('trial');
    expect($user->trialDaysLeft())->toBeGreaterThanOrEqual(6);
    expect($user->isOnTrial())->toBeTrue();
    expect($user->hasActiveSubscription())->toBeTrue();
    expect($user->isReadOnly())->toBeFalse();
});

it('read-only helpers flip for past_due / archived / cancelled', function () {
    foreach (['past_due', 'archived', 'cancelled'] as $status) {
        $user = makeUser($status, 'starter');
        expect($user->isReadOnly())->toBeTrue("expected {$status} to be read-only");
        expect($user->hasActiveSubscription())->toBeFalse("expected {$status} to lack active sub");
    }
});
