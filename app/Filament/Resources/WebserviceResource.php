<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GalleryOnly;
use App\Filament\Resources\WebserviceResource\Pages;
use App\Models\Webservice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebserviceResource extends Resource
{
    use GalleryOnly;

    protected static ?string $model = Webservice::class;
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'name';

    public const TYPES = [
        'mailchimp'   => 'Mailchimp',
        'woocommerce' => 'WooCommerce',
        'stripe'      => 'Stripe',
        'resend'      => 'Resend',
        'shopify'     => 'Shopify',
        'sendinblue'  => 'Sendinblue / Brevo',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\Select::make('type')->options(self::TYPES)->required(),
                ]),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),

            Forms\Components\Section::make('Configuration')
                ->description('API keys and settings. Stored encrypted in the database.')
                ->schema([
                    Forms\Components\KeyValue::make('config')
                        ->keyLabel('Setting')
                        ->valueLabel('Value')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Last sync')
                ->schema([
                    Forms\Components\Placeholder::make('last_sync_at')
                        ->content(fn (?Webservice $record) => $record?->last_sync_at?->format('d.m.Y H:i') ?? '—'),
                    Forms\Components\Placeholder::make('last_sync_status')
                        ->content(fn (?Webservice $record) => $record?->last_sync_status ?? '—'),
                    Forms\Components\Placeholder::make('last_sync_message')
                        ->content(fn (?Webservice $record) => $record?->last_sync_message ?? '—')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsed()
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (?string $state) => self::TYPES[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('last_sync_at')->dateTime('d.m.Y H:i')->placeholder('Never'),
                Tables\Columns\TextColumn::make('last_sync_status')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'success' => 'success',
                        'error'   => 'danger',
                        'running' => 'warning',
                        default   => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::TYPES),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index'  => Pages\ListWebservices::route('/'),
            'create' => Pages\CreateWebservice::route('/create'),
            'edit'   => Pages\EditWebservice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
