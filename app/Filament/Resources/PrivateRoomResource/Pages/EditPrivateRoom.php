<?php

namespace App\Filament\Resources\PrivateRoomResource\Pages;

use App\Filament\Resources\PrivateRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateRoom extends EditRecord
{
    protected static string $resource = PrivateRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Seed manual_lineup z aktuálneho pivot.position poradia.
        $orderedIds = $this->record->artworks()
            ->orderBy('private_room_artwork.position')
            ->pluck('artworks.id')
            ->all();
        $data['manual_lineup'] = collect($orderedIds)
            ->map(fn ($id) => ['artwork_id' => (int) $id])
            ->all();

        return $data;
    }
}
