<?php

namespace App\Filament\Resources\WebserviceResource\Pages;

use App\Filament\Resources\WebserviceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWebservice extends EditRecord
{
    protected static string $resource = WebserviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
