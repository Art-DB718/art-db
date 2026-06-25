<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Commerce';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public const PAYMENT_STATUSES = [
        'draft'     => 'Draft',
        'sent'      => 'Sent',
        'partial'   => 'Partially paid',
        'paid'      => 'Paid',
        'overdue'   => 'Overdue',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_METHODS = [
        'bank_transfer' => 'Bank transfer',
        'card'          => 'Card',
        'cash'          => 'Cash',
        'stripe'        => 'Stripe',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated on save (INV-YYYY-0001)'),
                    Forms\Components\Select::make('buyer_contact_id')
                        ->label('Buyer')
                        ->relationship('buyer', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn (Contact $record) => $record->display_name)
                        ->searchable(['first_name', 'last_name', 'organization'])
                        ->preload(),
                    Forms\Components\DatePicker::make('sale_date')->required()->default(now()),
                    Forms\Components\DatePicker::make('due_date'),
                    Forms\Components\Select::make('payment_status')
                        ->options(self::PAYMENT_STATUSES)->default('draft')->required(),
                    Forms\Components\Select::make('payment_method')
                        ->options(self::PAYMENT_METHODS),
                    Forms\Components\Select::make('currency')
                        ->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CZK' => 'CZK'])
                        ->default('EUR')->required(),
                ]),
            ]),

            Forms\Components\Section::make('Sold artworks')
                ->description('Add each artwork sold in this invoice.')
                ->icon('heroicon-m-photo')
                ->schema([
                    Forms\Components\Repeater::make('lineItems')
                        ->relationship()
                        ->hiddenLabel()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recomputeSaleTotals($get, $set))
                        ->schema([
                            Forms\Components\Select::make('artwork_id')
                                ->label('Artwork')
                                ->relationship('artwork', 'title')
                                ->searchable(['title', 'inventory_id'])
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set): void {
                                    if ($state && $artwork = Artwork::with('artist')->find($state)) {
                                        $set('description', trim($artwork->title.' — '.($artwork->artist->display_name ?? '')));
                                        $set('unit_price', $artwork->price);
                                    }
                                    self::recomputeSaleTotals($get, $set, '../../');
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('description')
                                ->required()->maxLength(255)->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()->default(1)->minValue(1)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recomputeSaleTotals($get, $set, '../../')),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()->prefix('€')->default(0)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recomputeSaleTotals($get, $set, '../../')),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->orderColumn('position')
                        ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                        ->addActionLabel('Add artwork'),
                ]),

            Forms\Components\Section::make('Totals')
                ->description('Subtotal updates live from the sold artworks above. Use the buttons below to apply a quick discount on the subtotal.')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()->prefix('€')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('tax_rate')
                            ->numeric()->suffix('%')->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recomputeSaleTotals($get, $set))
                            ->helperText('VAT rate.'),
                        Forms\Components\TextInput::make('tax_amount')
                            ->numeric()->prefix('€')->disabled()->dehydrated(false),
                    ]),
                    Forms\Components\ToggleButtons::make('discount_percent')
                        ->label('Quick discount')
                        ->options([
                            0  => 'None',
                            10 => '-10 %',
                            15 => '-15 %',
                            20 => '-20 %',
                            25 => '-25 %',
                        ])
                        ->colors([
                            0  => 'gray',
                            10 => 'warning',
                            15 => 'warning',
                            20 => 'danger',
                            25 => 'danger',
                        ])
                        ->default(0)
                        ->inline()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set): void {
                            $subtotal = (float) ($get('subtotal') ?? 0);
                            $discount = round($subtotal * ((int) $state) / 100, 2);
                            $set('discount_amount', number_format($discount, 2, '.', ''));
                            self::recomputeSaleTotals($get, $set);
                        }),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()->prefix('€')->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recomputeSaleTotals($get, $set)),
                        Forms\Components\TextInput::make('total')
                            ->numeric()->prefix('€')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('paid_amount')
                            ->numeric()->prefix('€')->default(0),
                    ]),
                ]),

            Forms\Components\Section::make('Notes')->schema([
                Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull()
                    ->helperText('Visible to the buyer on the invoice.'),
                Forms\Components\Textarea::make('internal_notes')->rows(3)->columnSpanFull()
                    ->helperText('Internal only — not shown to the buyer.'),
            ])->collapsed(),
        ]);
    }

    /**
     * Živo prepočíta subtotal (suma quantity × unit_price), tax_amount a total.
     * $scope umožňuje volať z vnútra Repeater itemu ('../../') aj z top-level polí.
     */
    protected static function recomputeSaleTotals(Forms\Get $get, Forms\Set $set, string $scope = ''): void
    {
        $items = $get($scope.'lineItems') ?? [];
        $subtotal = 0.0;
        foreach ((array) $items as $item) {
            $subtotal += ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0));
        }

        $taxRate   = (float) ($get($scope.'tax_rate') ?? 0);
        $discount  = (float) ($get($scope.'discount_amount') ?? 0);
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total     = round($subtotal + $taxAmount - $discount, 2);

        $set($scope.'subtotal',   number_format($subtotal, 2, '.', ''));
        $set($scope.'tax_amount', number_format($taxAmount, 2, '.', ''));
        $set($scope.'total',      number_format($total, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->searchable()->sortable()
                    ->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('buyer.last_name')->label('Buyer')
                    ->formatStateUsing(fn ($state, $record) => $record->buyer?->display_name ?? '—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sale_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge()
                    ->formatStateUsing(fn (?string $state) => self::PAYMENT_STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'paid'      => 'success',
                        'partial'   => 'warning',
                        'overdue'   => 'danger',
                        'cancelled' => 'gray',
                        default     => 'info',
                    }),
                Tables\Columns\TextColumn::make('total')->money(fn ($record) => $record->currency ?? 'EUR')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->money(fn ($record) => $record->currency ?? 'EUR')
                    ->toggleable(),
            ])
            ->defaultSort('sale_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')->options(self::PAYMENT_STATUSES),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('print')
                        ->label('Print invoice')
                        ->icon('heroicon-m-printer')
                        ->color('info')
                        ->url(fn (Sale $record): string => route('sales.print', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('pdf')
                        ->label('Download invoice (PDF)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Sale $record): string => route('sales.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('sendPrivateRoom')
                        ->label('Send Private Room')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('gray')
                        ->visible(fn (Sale $record) => (bool) $record->buyer_contact_id)
                        ->form([
                            Forms\Components\Select::make('private_room_id')
                                ->label('Private Room')
                                ->options(
                                    \App\Models\PrivateRoom::query()
                                        ->orderByDesc('updated_at')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($r) => [$r->id => $r->title.' ('.$r->artworks()->count().' works)'])
                                )
                                ->searchable()
                                ->required(),
                            Forms\Components\Textarea::make('note')
                                ->label('Personal note (optional)')
                                ->rows(3),
                        ])
                        ->action(function (Sale $record, array $data) {
                            $contact = $record->buyer;
                            if (! $contact || ! $contact->email) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot send')
                                    ->body('Buyer has no email on file.')
                                    ->danger()->send();
                                return;
                            }

                            $room = \App\Models\PrivateRoom::find($data['private_room_id']);
                            if (! $room) return;

                            $room->recipients()->syncWithoutDetaching([
                                $contact->id => ['status' => 'sent', 'sent_at' => now()],
                            ]);
                            $room->forceFill(['sent_at' => now()])->save();

                            $url = $room->publicUrl();
                            $greeting = trim('Dear '.($contact->first_name ?: $contact->display_name));
                            $html = '<p>'.e($greeting).',</p>'
                                .($data['note'] ?? null ? '<p>'.nl2br(e($data['note'])).'</p>' : '')
                                .'<p>You are invited to a private viewing: <strong>'.e($room->title).'</strong>.</p>'
                                .'<p><a href="'.e($url).'" style="display:inline-block;padding:12px 24px;background:#111827;color:#fff;text-decoration:none;letter-spacing:0.1em;text-transform:uppercase;font-size:0.75rem;">Open private viewing</a></p>'
                                .'<p style="color:#6b7280">Or copy this link: '.e($url).'</p>';

                            try {
                                \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($contact, $room) {
                                    $m->to($contact->email)
                                      ->subject('Private viewing: '.$room->title);
                                });
                                \Filament\Notifications\Notification::make()
                                    ->title('Private Room sent')
                                    ->body('Sent to '.$contact->email)
                                    ->success()->send();
                            } catch (\Throwable $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to send')
                                    ->body($e->getMessage())
                                    ->danger()->send();
                            }
                        })
                        ->modalSubmitActionLabel('Send'),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
