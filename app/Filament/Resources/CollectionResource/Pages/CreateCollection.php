<?php

namespace App\Filament\Resources\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    /** Auto-attach owner_user_id so the creator can edit their collection later. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check() && empty($data['owner_user_id'])) {
            $data['owner_user_id'] = auth()->id();
        }
        return $data;
    }

    /** After save, jump back to the list so the new collection is visible immediately. */
    protected function getRedirectUrl(): string
    {
        return CollectionResource::getUrl('index');
    }
}
