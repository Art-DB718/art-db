<?php

namespace App\Filament\Resources\MediumResource\Pages;

use App\Filament\Resources\MediumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedium extends EditRecord
{
    protected static string $resource = MediumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
