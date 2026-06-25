<?php

namespace App\Filament\Resources\ArtworkResource\Pages;

use App\Filament\Resources\ArtworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class ListArtworks extends ListRecords
{
    protected static string $resource = ArtworkResource::class;

    /** Aktívny režim zobrazenia tabuľky: list | gallery | cards (uložené aj v URL). */
    #[Url]
    public string $viewMode = 'list';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewList')
                ->label('List')
                ->icon('heroicon-m-list-bullet')
                ->color(fn (): string => $this->viewMode === 'list' ? 'primary' : 'gray')
                ->action(fn () => $this->viewMode = 'list'),
            Actions\Action::make('viewGallery')
                ->label('Gallery')
                ->icon('heroicon-m-photo')
                ->color(fn (): string => $this->viewMode === 'gallery' ? 'primary' : 'gray')
                ->action(fn () => $this->viewMode = 'gallery'),
            Actions\Action::make('viewCards')
                ->label('Cards')
                ->icon('heroicon-m-squares-2x2')
                ->color(fn (): string => $this->viewMode === 'cards' ? 'primary' : 'gray')
                ->action(fn () => $this->viewMode = 'cards'),
            Actions\Action::make('exportExcel')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Toggle::make('published_only')
                        ->label('Published only'),
                ])
                ->action(function (array $data) {
                    return (new \App\Exports\ArtworksExport(
                        publishedOnly: ($data['published_only'] ?? false) === true ? true : null,
                    ))->download('artworks-'.now()->format('Y-m-d').'.xlsx');
                }),
            Actions\CreateAction::make()
                ->label('New artwork'),
        ];
    }

    public function table(Table $table): Table
    {
        // Štandardný table config (kolumny + filtre + akcie + bulk z ArtworkResource).
        $table = static::getResource()::table($table);

        return match ($this->viewMode) {
            'gallery' => $table
                ->columns([
                    Stack::make([
                        ImageColumn::make('primary_image')
                            ->disk('public')
                            ->square()
                            ->height(240)
                            ->extraImgAttributes(['style' => 'width:100%;object-fit:cover;border-radius:6px;']),
                        TextColumn::make('artist.last_name')
                            ->label('Artist')
                            ->formatStateUsing(fn ($record): string => $record->artist?->display_name ?? '—')
                            ->weight('bold')
                            ->size('sm'),
                        TextColumn::make('title')
                            ->size('xs')
                            ->color('gray')
                            ->limit(48),
                    ])->space(2),
                ])
                ->contentGrid(['md' => 2, 'lg' => 3, 'xl' => 4]),

            'cards' => $table
                ->columns([
                    Stack::make([
                        ImageColumn::make('primary_image')
                            ->disk('public')
                            ->square()
                            ->height(180)
                            ->extraImgAttributes(['style' => 'width:100%;object-fit:cover;border-radius:6px;']),
                        TextColumn::make('artist.last_name')
                            ->label('Artist')
                            ->formatStateUsing(fn ($record): string => $record->artist?->display_name ?? '—')
                            ->weight('bold'),
                        TextColumn::make('title')->color('gray')->limit(60),
                        Split::make([
                            TextColumn::make('year_created')->size('xs')->color('gray'),
                            TextColumn::make('inventory_id')
                                ->fontFamily('mono')->size('xs')->color('gray'),
                        ]),
                        TextColumn::make('price')
                            ->money(fn ($record) => $record->currency ?? 'EUR')
                            ->size('sm')
                            ->weight('semibold'),
                        TextColumn::make('status.name')
                            ->badge()
                            ->color(fn ($record) => $record->status?->color ?? 'gray')
                            ->size('xs'),
                    ])->space(2),
                ])
                ->contentGrid(['md' => 2, 'lg' => 3]),

            default => $table, // 'list' — zachovaj predvolené stĺpce z ArtworkResource
        };
    }
}
