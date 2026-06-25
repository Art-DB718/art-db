<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GalleryOnly;
use App\Filament\Resources\ContactGroupResource\Pages;
use App\Models\ContactGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactGroupResource extends Resource
{
    use GalleryOnly;

    protected static ?string $model = ContactGroup::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'CRM';

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
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload()
                ->label('Parent group (optional)'),
            Forms\Components\Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('contacts_count')->counts('contacts')->label('Contacts'),
                Tables\Columns\TextColumn::make('description')->limit(50)->toggleable(),
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
            'index'  => Pages\ListContactGroups::route('/'),
            'create' => Pages\CreateContactGroup::route('/create'),
            'edit'   => Pages\EditContactGroup::route('/{record}/edit'),
        ];
    }
}
