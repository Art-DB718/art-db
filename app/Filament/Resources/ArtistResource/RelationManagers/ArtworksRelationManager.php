<?php

namespace App\Filament\Resources\ArtistResource\RelationManagers;

use App\Filament\Resources\ArtworkResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Artworks by this artist — shown as a table below the Artist edit form
 * so Kat can browse / open / add works without leaving the artist page.
 */
class ArtworksRelationManager extends RelationManager
{
    protected static string $relationship = 'artworks';

    protected static ?string $title = 'Artworks by this artist';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        // Not really used — Kat uses "Full editor" (below) to open the
        // real Artwork form. Kept minimal so the modal doesn't misbehave.
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\TextInput::make('inventory_id')->maxLength(64),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')->disk('public')->square()->size(50),
                Tables\Columns\TextColumn::make('inventory_id')->fontFamily('mono')->size('xs')->sortable(),
                Tables\Columns\TextColumn::make('title')->limit(40)->sortable()->searchable(),
                Tables\Columns\TextColumn::make('year_created')->sortable(),
                Tables\Columns\TextColumn::make('medium.name')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                        'sold' => 'danger',
                        'for sale', 'for sale or rent', 'for rent' => 'success',
                        'not for sale', 'not available' => 'warning',
                        'details pending' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')->money(fn ($record) => $record->currency ?? 'EUR')->sortable(),
            ])
            ->defaultSort('year_created', 'desc')
            ->actions([
                // "Full editor" opens the full ArtworkResource edit page in
                // a new tab — the mini form here doesn't cover images,
                // pricing, provenance, etc.
                Tables\Actions\Action::make('open')
                    ->label('Full editor')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn ($record) => ArtworkResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                // Quick-create a new artwork already attached to this artist.
                Tables\Actions\Action::make('newArtwork')
                    ->label('New artwork')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => ArtworkResource::getUrl('create', ['artist_id' => $this->getOwnerRecord()->id]))
                    ->openUrlInNewTab(),
            ]);
    }
}
