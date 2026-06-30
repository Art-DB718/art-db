<?php

namespace App\Providers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use App\Observers\ArtistObserver;
use App\Observers\ArtworkObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admin role bypasses všetky policies.
        Gate::before(function (?User $user) {
            return $user?->isAdmin() ? true : null;
        });

        // Per-plan limit enforcement — observers block inserts when the
        // owning user has hit their plan's artwork / artist cap.
        Artwork::observe(ArtworkObserver::class);
        Artist::observe(ArtistObserver::class);
    }
}
