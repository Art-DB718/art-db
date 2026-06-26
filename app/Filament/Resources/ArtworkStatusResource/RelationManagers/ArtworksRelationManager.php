<?php

namespace App\Filament\Resources\ArtworkStatusResource\RelationManagers;

use App\Filament\Resources\ArtworkResource;
use App\Models\Artwork;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ArtworksRelationManager extends RelationManager
{
    protected static string $relationship = 'artworks';
    protected static ?string $title = 'Artworks with this status';
    protected static ?string $icon = 'heroicon-o-photo';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')->disk('public')->square()->size(40),
                Tables\Columns\TextColumn::make('inventory_id')->label('Inv.')->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('artist.last_name')
                    ->label('Artist')
                    ->formatStateUsing(fn (Artwork $r) => $r->artist?->display_name ?? '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('medium.name')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('year_created')->sortable(),
                Tables\Columns\TextColumn::make('price')->money(fn (Artwork $r) => $r->currency ?? 'EUR'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Public'),
            ])
            ->recordUrl(fn (Artwork $r): string => ArtworkResource::getUrl('edit', ['record' => $r]))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No artworks have this status yet.');
    }
}
