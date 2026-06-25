<?php

namespace App\Filament\Resources\ArtworkStatusResource\Pages;

use App\Filament\Resources\ArtworkStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArtworkStatuses extends ListRecords
{
    protected static string $resource = ArtworkStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
