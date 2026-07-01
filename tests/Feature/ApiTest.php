<?php

use App\Enums\UserRole;
use App\Models\User;

// REST API v1 + Sanctum smoke tests.

it('serves the public v1 endpoints with 200 + JSON envelope', function () {
    foreach (['artworks', 'artists', 'galleries', 'exhibitions', 'collections'] as $resource) {
        $response = $this->getJson("/api/v1/{$resource}");
        expect($response->status())->toBe(200, "GET /api/v1/{$resource} should be 200");
        $response->assertJsonStructure(['data']);
    }
});

it('issues a Sanctum token for valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret-pass'),
        'role'     => UserRole::Gallery,
    ]);

    $response = $this->postJson('/api/v1/tokens', [
        'email'       => $user->email,
        'password'    => 'secret-pass',
        'device_name' => 'test-suite',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email', 'role']])
        ->assertJson(['token_type' => 'Bearer']);

    expect($response->json('token'))->toStartWith('1|');
});

it('rejects Sanctum token issue with bad password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);
    $this->postJson('/api/v1/tokens', [
        'email'    => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('returns the authenticated user via GET /api/v1/user with a bearer token', function () {
    $user  = User::factory()->create(['role' => UserRole::Artist]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJson([
            'id'    => $user->id,
            'email' => $user->email,
            'role'  => 'artist',
        ]);
});

it('rejects GET /api/v1/user without a token', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});
