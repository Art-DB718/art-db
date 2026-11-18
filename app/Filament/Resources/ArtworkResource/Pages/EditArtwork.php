<?php

namespace App\Filament\Resources\ArtworkResource\Pages;

use App\Filament\Resources\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Artwork $record */
        $record = $this->getRecord();

        $slug = Str::slug(($record->artist?->last_name ?? 'artwork').'-'.$record->title);

        $shareAttrs = fn (string $kind, string $route) => [
            'data-pdf-share'    => route($route, $record),
            'data-pdf-filename' => $kind.'-'.$slug.'.pdf',
        ];

        return [
            Actions\ActionGroup::make([
                Actions\Action::make('printCard')
                    ->label('Print artwork card')
                    ->icon('heroicon-m-printer')
                    ->url(route('artworks.pdf.card', $record))
                    ->openUrlInNewTab()
                    ->extraAttributes($shareAttrs('card', 'artworks.pdf.card')),
                Actions\Action::make('printCertificate')
                    ->label('Print certificate')
                    ->icon('heroicon-m-shield-check')
                    ->visible(! auth()->user()?->isCollector())
                    ->url(route('artworks.pdf.certificate', $record))
                    ->openUrlInNewTab()
                    ->extraAttributes($shareAttrs('certificate', 'artworks.pdf.certificate')),
                Actions\Action::make('printLabel')
                    ->label('Print label')
                    ->icon('heroicon-m-tag')
                    ->url(route('artworks.pdf.label', $record))
                    ->openUrlInNewTab()
                    ->extraAttributes($shareAttrs('label', 'artworks.pdf.label')),
                Actions\Action::make('printMaintenance')
                    ->label('Print maintenance report')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->visible(! auth()->user()?->isArtist())
                    ->url(route('artworks.pdf.maintenance', $record))
                    ->openUrlInNewTab()
                    ->extraAttributes($shareAttrs('maintenance', 'artworks.pdf.maintenance')),
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
