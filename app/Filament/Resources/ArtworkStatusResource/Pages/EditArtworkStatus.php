<?php

namespace App\Filament\Resources\ArtworkStatusResource\Pages;

use App\Filament\Resources\ArtworkStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtworkStatus extends EditRecord
{
    protected static string $resource = ArtworkStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
