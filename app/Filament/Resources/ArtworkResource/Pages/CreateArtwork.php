<?php

namespace App\Filament\Resources\ArtworkResource\Pages;

use App\Filament\Resources\ArtworkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtwork extends CreateRecord
{
    protected static string $resource = ArtworkResource::class;

    /**
     * Auto-attach owner_user_id for non-Gallery roles so the new artwork
     * shows up in their scoped list view (which filters by owner_user_id).
     * Gallery doesn't need this — they see all represented artworks.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (($user?->isArtist() || $user?->isCollector()) && empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $user->id;
        }
        // Artist user: every new artwork is auto-attached to their own artist profile.
        if ($user?->isArtist() && empty($data['artist_id']) && $user->artistProfile) {
            $data['artist_id'] = $user->artistProfile->id;
        }
        return $data;
    }

    /** After save, jump back to the artworks list so the newly-created row is visible. */
    protected function getRedirectUrl(): string
    {
        return ArtworkResource::getUrl('index');
    }
}
