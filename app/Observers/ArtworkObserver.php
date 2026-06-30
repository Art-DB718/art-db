<?php

namespace App\Observers;

use App\Models\Artwork;
use App\Services\PlanLimits;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ArtworkObserver
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    /**
     * Block new Artwork creation once the owner has hit their plan cap.
     * The owner is the user the artwork is being created under — for Filament
     * forms that's auth()->user() (set via owner_user_id assignment), and we
     * fall back to the auth user if owner_user_id is not yet on the model.
     */
    public function creating(Artwork $artwork): bool
    {
        $owner = $artwork->owner ?? auth()->user();
        if (! $owner) {
            return true; // CLI / seeder / system — no limit enforcement
        }

        if ($this->limits->hasReached($owner, PlanLimits::ARTWORKS)) {
            $cap     = $this->limits->limit($owner, PlanLimits::ARTWORKS);
            $planKey = $owner->subscription_plan ?: 'your current plan';

            // Filament notification shows up in the admin UI; a plain
            // ValidationException keeps the API / public paths honest too.
            if (function_exists('filament') && filament()->hasPlugin('app')) {
                // no-op guard — we don't gate on plugin, but keep room for it
            }

            try {
                Notification::make()
                    ->title('Artwork limit reached')
                    ->body("You've reached the {$cap}-artwork cap on {$planKey}. Upgrade your plan to add more.")
                    ->danger()
                    ->persistent()
                    ->send();
            } catch (\Throwable) {
                // Filament not booted (CLI/API context) — fall through to exception.
            }

            throw ValidationException::withMessages([
                'plan_limit' => "Artwork limit reached ({$cap}). Please upgrade your plan.",
            ]);
        }

        return true;
    }
}
