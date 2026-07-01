<?php

use App\Enums\UserRole;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use App\Services\PlanLimits;
use Illuminate\Validation\ValidationException;

// Per-plan enforcement via observers + PlanLimits reads.

beforeEach(function () {
    $this->limits = app(PlanLimits::class);
    $this->artist = Artist::create(['first_name' => 'A', 'last_name' => 'B', 'is_published' => true]);
});

it('reports unlimited for a plan with no cap', function () {
    $user = User::factory()->create([
        'role'                => UserRole::Gallery,
        'subscription_plan'   => 'studio',
        'subscription_status' => 'active',
    ]);
    // Studio's 'artworks' limit is null (unlimited) in config
    expect($this->limits->limit($user, PlanLimits::ARTWORKS))->toBeNull();
    expect($this->limits->hasReached($user, PlanLimits::ARTWORKS))->toBeFalse();
});

it('counts owned artworks per user', function () {
    $user = User::factory()->create();
    expect($this->limits->usage($user, PlanLimits::ARTWORKS))->toBe(0);

    Artwork::create([
        'artist_id'     => $this->artist->id,
        'title'         => 'One',
        'owner_user_id' => $user->id,
    ]);
    expect($this->limits->usage($user, PlanLimits::ARTWORKS))->toBe(1);
});

it('blocks Artwork::create when the owner has reached the artist_free cap', function () {
    $user = User::factory()->create([
        'role'                => UserRole::Artist,
        'subscription_plan'   => 'artist_free',   // limit 20 artworks
        'subscription_status' => 'active',
    ]);

    // Bulk-insert past the cap via raw DB — bypasses observer AND populates
    // the not-null uuid/slug/inventory_id fields the model normally auto-fills.
    for ($i = 0; $i < 20; $i++) {
        \DB::table('artworks')->insert([
            'uuid'          => (string) \Illuminate\Support\Str::uuid(),
            'slug'          => "fill-{$i}-".\Illuminate\Support\Str::random(4),
            'inventory_id'  => 'INV-TEST-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'artist_id'     => $this->artist->id,
            'title'         => "Fill {$i}",
            'owner_user_id' => $user->id,
            'currency'      => 'EUR',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
    expect($this->limits->usage($user, PlanLimits::ARTWORKS))->toBe(20);
    expect($this->limits->hasReached($user, PlanLimits::ARTWORKS))->toBeTrue();

    // Now try the 21st — observer should throw ValidationException
    auth()->login($user);
    expect(fn () => Artwork::create([
        'artist_id'     => $this->artist->id,
        'title'         => 'Over the cap',
        'owner_user_id' => $user->id,
    ]))->toThrow(ValidationException::class);
});

it('storage_used_bytes → GB conversion via PlanLimits::usage', function () {
    $user = User::factory()->create();
    $user->forceFill(['storage_used_bytes' => (int) (2.5 * 1024 * 1024 * 1024)])->save();
    expect($this->limits->usage($user, PlanLimits::STORAGE))->toBe(2.5);
});
