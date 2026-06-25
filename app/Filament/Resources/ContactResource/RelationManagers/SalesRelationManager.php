<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';
    protected static ?string $title = 'Sales';
    protected static ?string $icon = 'heroicon-o-banknotes';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('sale_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'paid'      => 'success',
                        'partial'   => 'warning',
                        'overdue'   => 'danger',
                        'cancelled' => 'gray',
                        default     => 'info',
                    }),
                Tables\Columns\TextColumn::make('total')->money(fn ($record) => $record->currency ?? 'EUR'),
                Tables\Columns\TextColumn::make('paid_amount')->money(fn ($record) => $record->currency ?? 'EUR'),
            ])
            ->defaultSort('sale_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
