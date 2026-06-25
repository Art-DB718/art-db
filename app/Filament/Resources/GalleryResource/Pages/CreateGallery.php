<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    protected static string $resource = GalleryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user?->isGallery() && empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $user->id;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        if ($user?->isGallery()) {
            return GalleryResource::getUrl('edit', ['record' => $this->record]);
        }
        return parent::getRedirectUrl();
    }
}
