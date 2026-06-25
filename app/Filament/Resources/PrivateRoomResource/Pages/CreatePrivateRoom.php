<?php

namespace App\Filament\Resources\PrivateRoomResource\Pages;

use App\Filament\Resources\PrivateRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePrivateRoom extends CreateRecord
{
    protected static string $resource = PrivateRoomResource::class;

    protected function afterCreate(): void
    {
        // Manuálne poradie z Repeateru (manual_lineup), inak fallback na poradie v Select.
        $ids = collect($this->data['manual_lineup'] ?? [])
            ->pluck('artwork_id')
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->all();
        if (empty($ids)) {
            $ids = $this->data['artworks'] ?? [];
        }
        PrivateRoomResource::updateManualPositions($this->record, $ids);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
