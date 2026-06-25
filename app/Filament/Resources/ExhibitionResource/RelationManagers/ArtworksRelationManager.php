<?php

namespace App\Filament\Resources\ExhibitionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ArtworksRelationManager extends RelationManager
{
    protected static string $relationship = 'artworks';
    protected static ?string $title = 'Artworks';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('position')->numeric()->default(0),
            Forms\Components\Toggle::make('was_sold')->label('Sold at this exhibition'),
            Forms\Components\Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')->disk('public')->square()->size(44),
                Tables\Columns\TextColumn::make('inventory_id')->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('artist.last_name')->label('Artist'),
                Tables\Columns\TextColumn::make('position')->sortable(),
                Tables\Columns\IconColumn::make('was_sold')->boolean()->label('Sold'),
            ])
            ->defaultSort('artwork_exhibition.position')
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title', 'inventory_id'])
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('position')->numeric()->default(0),
                        Forms\Components\Toggle::make('was_sold')->label('Sold at this exhibition'),
                        Forms\Components\Textarea::make('notes')->rows(2),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
