<?php

namespace App\Filament\Resources\WebserviceResource\Pages;

use App\Filament\Resources\WebserviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWebservices extends ListRecords
{
    protected static string $resource = WebserviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
