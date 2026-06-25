<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Exports\SalesExport;
use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportExcel')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('From')
                        ->default(now()->startOfYear()),
                    Forms\Components\DatePicker::make('to')
                        ->label('To')
                        ->default(now()),
                    Forms\Components\Select::make('payment_status')
                        ->label('Payment status (optional)')
                        ->options([
                            'unpaid'  => 'Unpaid',
                            'partial' => 'Partial',
                            'paid'    => 'Paid',
                        ])
                        ->placeholder('All statuses'),
                ])
                ->action(function (array $data) {
                    $filename = 'sales-'
                        .($data['from'] ?? 'all').'-'
                        .($data['to'] ?? now()->format('Y-m-d'))
                        .'.xlsx';
                    return (new SalesExport(
                        from: $data['from'] ?? null,
                        to:   $data['to']   ?? null,
                        paymentStatus: $data['payment_status'] ?? null,
                    ))->download($filename);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
