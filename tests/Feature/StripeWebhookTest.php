<?php

use App\Models\User;

// Signature verification + subscription-sync behaviour of /stripe/webhook.
// Live Stripe API isn't hit — we hand-craft the HMAC header the same way
// Stripe does so the controller can verify offline.

beforeEach(function () {
    // Deterministic secret for signature generation in tests
    config(['services.stripe.webhook_secret' => 'whsec_test_secret_ABCDEF123456']);
});

function sign(string $payload, string $secret): string
{
    $ts = time();
    $signed = $ts.'.'.$payload;
    $v1 = hash_hmac('sha256', $signed, $secret);
    return "t={$ts},v1={$v1}";
}

it('rejects webhook with a bad signature (400)', function () {
    $this->postJson('/stripe/webhook',
        ['type' => 'customer.subscription.created'],
        ['Stripe-Signature' => 't=1234567890,v1=deadbeef']
    )->assertStatus(400);
});

it('processes customer.subscription.updated and syncs plan + status onto the user', function () {
    $user = User::factory()->create([
        'stripe_id'           => 'cus_TEST_123',
        'subscription_plan'   => null,
        'subscription_status' => 'trial',
    ]);
    // Fake a Stripe price ID that matches Pro monthly
    config(['subscription.plans.pro.stripe_price_monthly' => 'price_TEST_PRO_MONTHLY']);

    $payload = json_encode([
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'customer'  => 'cus_TEST_123',
                'status'    => 'active',
                'cancel_at' => null,
                'items'     => ['data' => [
                    ['price' => ['id' => 'price_TEST_PRO_MONTHLY']],
                ]],
            ],
        ],
    ]);

    $secret = config('services.stripe.webhook_secret');
    $this->call('POST', '/stripe/webhook',
        [], [], [],
        ['HTTP_STRIPE_SIGNATURE' => sign($payload, $secret), 'CONTENT_TYPE' => 'application/json'],
        $payload
    )->assertOk();

    $user->refresh();
    expect($user->subscription_plan)->toBe('pro');
    expect($user->subscription_status)->toBe('active');
});

it('flips user to cancelled on customer.subscription.deleted', function () {
    $user = User::factory()->create([
        'stripe_id'           => 'cus_TEST_DEL',
        'subscription_plan'   => 'pro',
        'subscription_status' => 'active',
    ]);

    $payload = json_encode([
        'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['customer' => 'cus_TEST_DEL']],
    ]);
    $secret = config('services.stripe.webhook_secret');
    $this->call('POST', '/stripe/webhook',
        [], [], [],
        ['HTTP_STRIPE_SIGNATURE' => sign($payload, $secret), 'CONTENT_TYPE' => 'application/json'],
        $payload
    )->assertOk();

    expect($user->fresh()->subscription_status)->toBe('cancelled');
});

it('flips user to past_due on invoice.payment_failed', function () {
    $user = User::factory()->create([
        'stripe_id'           => 'cus_TEST_PF',
        'subscription_plan'   => 'starter',
        'subscription_status' => 'active',
    ]);
    $payload = json_encode([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['customer' => 'cus_TEST_PF']],
    ]);
    $secret = config('services.stripe.webhook_secret');
    $this->call('POST', '/stripe/webhook',
        [], [], [],
        ['HTTP_STRIPE_SIGNATURE' => sign($payload, $secret), 'CONTENT_TYPE' => 'application/json'],
        $payload
    )->assertOk();

    expect($user->fresh()->subscription_status)->toBe('past_due');
});
