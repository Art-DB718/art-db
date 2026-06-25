<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    /** When an Artist/Collector creates a profile, auto-attach owner_user_id. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (($user?->isArtist() || $user?->isCollector()) && empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $user->id;
        }
        return $data;
    }

    /** When a Gallery creates a new artist, auto-attach them to their pivot. */
    protected function afterCreate(): void
    {
        $user = auth()->user();
        if ($user?->isGallery() && $user->gallery) {
            $user->gallery->artists()->syncWithoutDetaching([$this->record->id]);
        }
    }

    /** Artist user has only one profile — replace "Create another" with edit redirect. */
    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        if ($user?->isArtist()) {
            return ArtistResource::getUrl('edit', ['record' => $this->record]);
        }
        return parent::getRedirectUrl();
    }
}
