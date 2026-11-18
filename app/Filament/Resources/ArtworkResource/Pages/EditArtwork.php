<?php

namespace App\Filament\Resources\ArtworkResource\Pages;

use App\Filament\Resources\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Artwork $record */
        $record = $this->getRecord();

        return [
            Actions\ActionGroup::make([
                Actions\Action::make('printCard')
                    ->label('Print artwork card')
                    ->icon('heroicon-m-printer')
                    ->url(route('artworks.pdf.card', $record))
                    ->openUrlInNewTab(),
                Actions\Action::make('printCertificate')
                    ->label('Print certificate')
                    ->icon('heroicon-m-shield-check')
                    ->visible(! auth()->user()?->isCollector())
                    ->url(route('artworks.pdf.certificate', $record))
                    ->openUrlInNewTab(),
                Actions\Action::make('printLabel')
                    ->label('Print label')
                    ->icon('heroicon-m-tag')
                    ->url(route('artworks.pdf.label', $record))
                    ->openUrlInNewTab(),
                Actions\Action::make('printMaintenance')
                    ->label('Print maintenance report')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->visible(! auth()->user()?->isArtist())
                    ->url(route('artworks.pdf.maintenance', $record))
                    ->openUrlInNewTab(),
            ])
                ->label('Print / Save as PDF')
                ->icon('heroicon-m-printer')
                ->button(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
