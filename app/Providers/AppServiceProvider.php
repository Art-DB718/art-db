<?php

namespace App\Providers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use App\Observers\ArtistObserver;
use App\Observers\ArtworkObserver;
use App\Support\SearchNormalizer;
use Filament\Tables\Columns\Column;
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

        // Diacritics-insensitive Filament table search:
        // ->searchableAccentless()             — search on this column's name
        // ->searchableAccentless(['a', 'b'])   — search across the given columns
        // Passing `false` disables search, matching Filament's ->searchable(false).
        Column::macro('searchableAccentless', function (bool|array $condition = true) {
            /** @var Column $this */
            if ($condition === false) {
                return $this->searchable(false);
            }

            $columns = is_array($condition) ? $condition : [$this->getName()];

            return $this->searchable(
                condition: true,
                query: fn ($query, string $search) => SearchNormalizer::apply($query, $columns, $search),
            );
        });
    }
}
