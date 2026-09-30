<?php

namespace App\Filament\Resources\ContactGroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contacts inside a ContactGroup — many-to-many via contact_contact_group
 * pivot. Lets Kat view the group's members and add/remove them without
 * leaving the group edit screen.
 */
class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contacts in this group';

    protected static ?string $recordTitleAttribute = 'last_name';

    public function form(Form $form): Form
    {
        // Not really used — we attach existing contacts rather than create
        // new ones from here, but Filament requires a form definition.
        return $form->schema([
            Forms\Components\TextInput::make('first_name')->maxLength(255),
            Forms\Components\TextInput::make('last_name')->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last_name')
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->searchableAccentless()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->searchableAccentless()->sortable(),
                Tables\Columns\TextColumn::make('organization')->searchableAccentless()->toggleable(),
                Tables\Columns\TextColumn::make('email')->searchableAccentless()->copyable()->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('country.name')->label('Country')->toggleable(),
            ])
            ->defaultSort('last_name')
            ->headerActions([
                // "Add existing contacts" — searchable multi-select picker.
                Tables\Actions\AttachAction::make()
                    ->label('Add contacts')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'organization', 'email'])
                    ->recordSelectOptionsQuery(fn (Builder $q) => $q->orderBy('last_name')->orderBy('first_name')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DetachAction::make()
                    ->label('Remove from group')
                    ->modalHeading('Remove from group')
                    ->modalDescription('This only removes the contact from THIS group. The contact itself stays.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove selected from group'),
                ]),
            ]);
    }
}
