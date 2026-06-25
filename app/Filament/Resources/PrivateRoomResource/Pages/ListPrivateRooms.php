<?php

namespace App\Filament\Resources\PrivateRoomResource\Pages;

use App\Filament\Resources\PrivateRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateRooms extends ListRecords
{
    protected static string $resource = PrivateRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
