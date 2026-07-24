<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use App\Mail\ArtistClaimRequested;
use App\Models\Artist;
use App\Models\ArtistClaim;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    /**
     * Auto-attach owner_user_id to the current user for every non-admin
     * flow — matches the isolated-workspace scoping in getEloquentQuery,
     * so a Gallery user who just created an Artist immediately sees it
     * in their list.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && ! $user->isAdmin() && empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $user->id;
        }
        return $data;
    }

    /**
     * Duplicate-name check before creation — mirrors the auto-match logic in
     * Register.php. Runs for Artist users (they'd otherwise get a duplicate
     * profile with a random slug suffix); Gallery / Collector creates are
     * expected to add new artists to the roster, so they skip this check.
     */
    protected function beforeCreate(): void
    {
        $user = auth()->user();
        if (! $user?->isArtist()) {
            return;
        }

        $data      = $this->form->getState();
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName  = trim((string) ($data['last_name']  ?? ''));

        if ($firstName === '' || $lastName === '') {
            return;
        }

        $existing = Artist::query()
            ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($firstName)])
            ->whereRaw('LOWER(last_name) = ?',  [mb_strtolower($lastName)])
            ->first();

        if (! $existing || $existing->owner_user_id === $user->id) {
            return; // no match OR already ours — proceed with create
        }

        // Ownerless match → adopt directly, skip create.
        if (is_null($existing->owner_user_id)) {
            $existing->forceFill(['owner_user_id' => $user->id])->save();
            Notification::make()
                ->title('Existing profile linked')
                ->body("We found an unowned profile for {$firstName} {$lastName} and linked it to your account instead of creating a duplicate.")
                ->success()
                ->send();
            $this->redirect(ArtistResource::getUrl('edit', ['record' => $existing]));
            $this->halt();
        }

        // Owned by someone else — open a pending claim, skip create.
        $claim = ArtistClaim::firstOrCreate(
            [
                'artist_id'        => $existing->id,
                'claimant_user_id' => $user->id,
                'status'           => ArtistClaim::STATUS_PENDING,
            ],
        );

        if ($claim->wasRecentlyCreated && $existing->owner?->email) {
            try {
                Mail::to($existing->owner->email)->send(new ArtistClaimRequested($claim));
            } catch (\Throwable $e) {
                logger()->warning('Artist claim mail failed: '.$e->getMessage());
            }
        }

        Notification::make()
            ->title($claim->wasRecentlyCreated ? 'Existing profile found' : 'Claim already pending')
            ->body("A profile for {$firstName} {$lastName} already exists in the database. "
                 . ($claim->wasRecentlyCreated
                    ? 'The current owner has been notified — you\'ll get an email once they respond.'
                    : 'You already have an open request; wait for the current owner to respond.'))
            ->warning()
            ->persistent()
            ->send();

        $this->redirect(ArtistResource::getUrl('index'));
        $this->halt();
    }

    /** When a Gallery creates a new artist, auto-attach them to their pivot. */
    protected function afterCreate(): void
    {
        $user = auth()->user();
        if ($user?->isGallery() && $user->gallery) {
            $user->gallery->artists()->syncWithoutDetaching([$this->record->id]);
        }
    }

    /**
     * Redirect after save:
     * - Artist user: edit page (they own a single profile, keep editing)
     * - Everyone else: index/list (so the newly-created row is visible in the table)
     */
    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        if ($user?->isArtist()) {
            return ArtistResource::getUrl('edit', ['record' => $this->record]);
        }
        return ArtistResource::getUrl('index');
    }
}
