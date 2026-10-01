<?php

namespace App\Filament\Resources\PrivateRoomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recipients of this private room — who was invited, when the email went
 * out, and whether the recipient's mail client loaded the tracking pixel
 * (opened_at). Lives below the Private Room edit form.
 */
class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    protected static ?string $recordTitleAttribute = 'last_name';

    public function form(Form $form): Form
    {
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
                Tables\Columns\TextColumn::make('last_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('first_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->copyable()->icon('heroicon-m-envelope')->searchable(),

                Tables\Columns\TextColumn::make('pivot.sent_at')
                    ->label('Sent')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('— not sent —')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.opened_at')
                    ->label('Opened')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        filled($state)                               => 'Opened '.\Illuminate\Support\Carbon::parse($state)->format('d.m.Y H:i'),
                        filled($record->pivot->sent_at ?? null)      => 'Not opened yet',
                        default                                       => '—',
                    })
                    ->color(fn ($state, $record) => match (true) {
                        filled($state)                               => 'success',
                        filled($record->pivot->sent_at ?? null)      => 'gray',
                        default                                       => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('pivot_sent_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('opened')
                    ->label('Opened the email')
                    ->query(fn ($q) => $q->wherePivotNotNull('opened_at'))
                    ->toggle(),
                Tables\Filters\Filter::make('not_opened')
                    ->label('Sent but not opened')
                    ->query(fn ($q) => $q->wherePivotNotNull('sent_at')->wherePivotNull('opened_at'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Add recipient')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'organization', 'email']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DetachAction::make()
                    ->label('Remove'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
