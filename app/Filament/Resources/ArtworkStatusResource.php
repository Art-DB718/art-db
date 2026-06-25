<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GalleryOnly;
use App\Filament\Resources\ArtworkStatusResource\Pages;
use App\Models\ArtworkStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtworkStatusResource extends Resource
{
    use GalleryOnly;

    protected static ?string $model = ArtworkStatus::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Catalogue';

    public static function canViewAny(): bool { return auth()->check(); }
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'Artwork Statuses';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\ColorPicker::make('color')
                ->required()
                ->default('#999999'),
            Forms\Components\Toggle::make('is_public')
                ->label('Visible on public site')
                ->default(true),
            Forms\Components\Toggle::make('counts_as_available')
                ->label('Counts as available for sale')
                ->default(true),
            Forms\Components\TextInput::make('position')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\IconColumn::make('is_public')->boolean()->label('Public'),
                Tables\Columns\IconColumn::make('counts_as_available')->boolean()->label('Available'),
                Tables\Columns\TextColumn::make('artworks_count')->counts('artworks')->label('Artworks'),
                Tables\Columns\TextColumn::make('position')->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArtworkStatuses::route('/'),
            'create' => Pages\CreateArtworkStatus::route('/create'),
            'edit'   => Pages\EditArtworkStatus::route('/{record}/edit'),
        ];
    }
}
