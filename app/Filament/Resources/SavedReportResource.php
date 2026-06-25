<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GalleryOnly;
use App\Filament\Resources\SavedReportResource\Pages;
use App\Models\SavedReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SavedReportResource extends Resource
{
    use GalleryOnly;

    protected static ?string $model = SavedReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'System';

    public static function canViewAny(): bool { return auth()->check(); }
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Saved Reports';
    protected static ?string $recordTitleAttribute = 'name';

    public const TYPES = [
        'inventory_valuation' => 'Inventory valuation',
        'sales'               => 'Sales',
        'artist_summary'      => 'Artist summary',
        'exhibitions'         => 'Exhibitions',
        'contacts'            => 'Contacts',
    ];

    public const FORMATS = [
        'pdf'  => 'PDF',
        'xlsx' => 'Excel (XLSX)',
        'csv'  => 'CSV',
        'json' => 'JSON',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\Select::make('type')->options(self::TYPES)->required(),
                    Forms\Components\Select::make('output_format')
                        ->options(self::FORMATS)->default('pdf')->required(),
                    Forms\Components\TextInput::make('schedule')
                        ->placeholder('0 8 * * 1')
                        ->helperText('Optional cron expression for automatic runs.'),
                ]),
            ]),

            Forms\Components\Section::make('Filters')
                ->description('Report-specific filter parameters.')
                ->schema([
                    Forms\Components\KeyValue::make('filters')
                        ->keyLabel('Filter')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ])
                ->collapsed(),

            Forms\Components\Section::make('Recipients')->schema([
                Forms\Components\TagsInput::make('recipients')
                    ->placeholder('email@example.com')
                    ->helperText('Email addresses that receive the generated report.')
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Last run')->schema([
                Forms\Components\Placeholder::make('last_run_at')
                    ->content(fn (?SavedReport $record) => $record?->last_run_at?->format('d.m.Y H:i') ?? '—'),
                Forms\Components\Placeholder::make('last_run_status')
                    ->content(fn (?SavedReport $record) => $record?->last_run_status ?? '—'),
            ])->columns(2)->collapsed()->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (?string $state) => self::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('output_format')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $state) => self::FORMATS[$state] ?? $state),
                Tables\Columns\TextColumn::make('schedule')->placeholder('Manual')->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('last_run_at')->dateTime('d.m.Y H:i')->placeholder('Never'),
                Tables\Columns\TextColumn::make('last_run_status')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'success' => 'success',
                        'error'   => 'danger',
                        default   => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::TYPES),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSavedReports::route('/'),
            'create' => Pages\CreateSavedReport::route('/create'),
            'edit'   => Pages\EditSavedReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
