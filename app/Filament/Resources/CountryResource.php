<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GalleryOnly;
use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    use GalleryOnly;

    protected static ?string $model = Country::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?string $navigationGroup = 'System';

    public static function canViewAny(): bool { return auth()->check(); }
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('iso_alpha2')
                ->label('ISO alpha-2')
                ->required()
                ->maxLength(2)
                ->placeholder('SK'),
            Forms\Components\TextInput::make('iso_alpha3')
                ->label('ISO alpha-3')
                ->maxLength(3)
                ->placeholder('SVK'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('iso_alpha2')->label('Alpha-2')->badge(),
                Tables\Columns\TextColumn::make('iso_alpha3')->label('Alpha-3')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('artists_count')->counts('artists')->label('Artists'),
                Tables\Columns\TextColumn::make('contacts_count')->counts('contacts')->label('Contacts'),
            ])
            ->defaultSort('name')
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
            'index'  => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit'   => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
