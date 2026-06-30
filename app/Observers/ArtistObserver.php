<?php

namespace App\Observers;

use App\Models\Artist;
use App\Services\PlanLimits;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ArtistObserver
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    /**
     * Block new Artist creation when the owning user hits their cap.
     * This catches the Collector case (private artist archive) and the
     * Gallery 'create new artist' flow (a fresh Artist row, then pivot
     * attach happens after). Gallery's *attach existing artist* doesn't
     * pass through here — that's handled in the Filament Resource.
     */
    public function creating(Artist $artist): bool
    {
        $owner = $artist->owner ?? auth()->user();
        if (! $owner) {
            return true; // CLI / seeder context
        }

        // Artists isn't a meaningful cap for Artist users (they have 1 fixed
        // profile); skip the check for them so they can create themselves.
        if ($owner->role?->value === 'artist') {
            return true;
        }

        if ($this->limits->hasReached($owner, PlanLimits::ARTISTS)) {
            $cap     = $this->limits->limit($owner, PlanLimits::ARTISTS);
            $planKey = $owner->subscription_plan ?: 'your current plan';

            try {
                Notification::make()
                    ->title('Artists limit reached')
                    ->body("You've reached the {$cap}-artist cap on {$planKey}. Upgrade to add more.")
                    ->danger()
                    ->persistent()
                    ->send();
            } catch (\Throwable) {
                // Not in a Filament request — fall through to exception below.
            }

            throw ValidationException::withMessages([
                'plan_limit' => "Artists limit reached ({$cap}). Please upgrade your plan.",
            ]);
        }

        return true;
    }
}
