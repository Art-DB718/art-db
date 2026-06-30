<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;

/**
 * Per-plan resource limits — single source of truth for "how much can this
 * user create" queries. Consumed by:
 *   - Eloquent observers (block insert when cap reached)
 *   - Filament Resource pages (disable Create button)
 *   - Dashboard UsageWidget (X / Y bars)
 */
class PlanLimits
{
    /** Resources we track. Matches keys in config('subscription.plans.X.limits'). */
    public const ARTWORKS = 'artworks';
    public const ARTISTS  = 'artists';
    public const STORAGE  = 'storage_gb';

    /**
     * Current usage count for a given user + resource.
     * Returns int for counts (artworks/artists), float GB for storage.
     */
    public function usage(User $user, string $resource): int|float
    {
        return match ($resource) {
            self::ARTWORKS => Artwork::query()->where('owner_user_id', $user->id)->count(),
            self::ARTISTS  => $this->artistsUsage($user),
            self::STORAGE  => 0.0, // TODO: implement disk-usage scan in a follow-up
            default        => 0,
        };
    }

    /**
     * Per-plan cap for a resource. null = unlimited; 0 means no quota assigned
     * (treat as unlimited too so we don't accidentally lock users out of a
     * resource the plan didn't define).
     */
    public function limit(User $user, string $resource): ?int
    {
        $planKey = $user->subscription_plan ?: ($user->subscription_status === 'trial' ? 'trial' : null);
        if (! $planKey) {
            return null;
        }
        $limit = config("subscription.plans.{$planKey}.limits.{$resource}");
        if ($limit === null || $limit === 0) {
            return null;
        }
        return (int) $limit;
    }

    /** True when the user has hit (or exceeded) the cap for this resource. */
    public function hasReached(User $user, string $resource): bool
    {
        $limit = $this->limit($user, $resource);
        if ($limit === null) {
            return false; // unlimited
        }
        return $this->usage($user, $resource) >= $limit;
    }

    /** Items still available before the cap; null when unlimited. */
    public function remaining(User $user, string $resource): ?int
    {
        $limit = $this->limit($user, $resource);
        if ($limit === null) {
            return null;
        }
        return max(0, $limit - (int) $this->usage($user, $resource));
    }

    /**
     * Artists usage depends on the user's role:
     *   - Collector: Artists they own (private archive)
     *   - Gallery:   Artists represented by this gallery (via artist_gallery pivot)
     *   - Artist:    always 1 — their own profile (limit irrelevant)
     *   - Admin:     0 — admins don't have a billing-bound 'roster'
     */
    protected function artistsUsage(User $user): int
    {
        $role = $user->role?->value;

        return match ($role) {
            'collector' => Artist::query()->where('owner_user_id', $user->id)->count(),
            'gallery'   => $user->gallery
                ? $user->gallery->artists()->count()
                : 0,
            'artist'    => 1,
            default     => 0,
        };
    }
}
