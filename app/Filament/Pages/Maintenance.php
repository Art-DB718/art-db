<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ArtworkResource;
use App\Models\Artwork;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Maintenance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Maintenance';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?string $title           = 'Maintenance';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.pages.maintenance';

    /** Badge with count of works currently in restoration. */
    public static function getNavigationBadge(): ?string
    {
        $count = Artwork::query()
            ->whereHas('maintenances', fn ($q) => $q->whereNull('restoration_returned_at'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Artwork::query()->has('maintenances')->with(['artist', 'maintenances']))
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')->disk('public')->square()->size(50),
                Tables\Columns\TextColumn::make('inventory_id')->label('Inv. ID')->searchable()->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('artist.last_name')
                    ->label('Artist')
                    ->formatStateUsing(fn (Artwork $record) => trim(($record->artist?->first_name ?? '').' '.($record->artist?->last_name ?? '')) ?: '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenances_count')
                    ->counts('maintenances')
                    ->label('Records')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latest_restoration_date')
                    ->label('Latest sent')
                    ->state(fn (Artwork $record) => $record->maintenances->first()?->restoration_date)
                    ->date()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('latest_returned_at')
                    ->label('Latest returned')
                    ->state(fn (Artwork $record) => $record->maintenances->first()?->restoration_returned_at)
                    ->date()
                    ->placeholder('— still in restoration'),
                Tables\Columns\TextColumn::make('maintenance_total_cost')
                    ->label('Total cost')
                    ->state(fn (Artwork $record) => $record->maintenance_total_cost)
                    ->money('EUR')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('maintenance_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Artwork $record): string => $record->maintenance_status)
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In restoration',
                        'returned'    => 'Returned',
                        default       => '—',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'returned'    => 'success',
                        default       => 'gray',
                    })
                    ->icon(fn (string $state): ?string => match ($state) {
                        'in_progress' => 'heroicon-m-wrench-screwdriver',
                        'returned'    => 'heroicon-m-check-circle',
                        default       => null,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('maintenance_status')
                    ->label('Status')
                    ->options([
                        'in_progress' => 'In restoration (any record open)',
                        'returned'    => 'All returned',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value === 'in_progress') {
                            return $query->whereHas('maintenances', fn ($q) => $q->whereNull('restoration_returned_at'));
                        }
                        if ($value === 'returned') {
                            return $query->whereDoesntHave('maintenances', fn ($q) => $q->whereNull('restoration_returned_at'));
                        }
                        return $query;
                    }),
            ])
            ->recordUrl(fn (Artwork $record): string => ArtworkResource::getUrl('edit', ['record' => $record]).'?tab=-maintenance-tab')
            ->actions([
                Tables\Actions\Action::make('report')
                    ->label('Report')
                    ->icon('heroicon-m-document-text')
                    ->url(fn (Artwork $record): string => route('artworks.print.maintenance', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Artwork $record): string => route('artworks.pdf.maintenance', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No artworks have any maintenance records yet.')
            ->emptyStateDescription('Open an artwork → Maintenance tab to add restoration details.');
    }
}
