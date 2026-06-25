<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtworkResource\Pages;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArtworkResource extends Resource
{
    protected static ?string $model = Artwork::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Artwork')
                ->persistTabInQueryString('tab')
                ->tabs([

                Forms\Components\Tabs\Tab::make('Basic')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpan(2),
                        Forms\Components\Select::make('artist_id')
                            ->relationship('artist', 'last_name')
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Artist $record) => $record->display_name)
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => ! auth()->user()?->isArtist())
                            ->createOptionForm([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('first_name')->required(),
                                    Forms\Components\TextInput::make('last_name')->required(),
                                    Forms\Components\TextInput::make('birth_year')->numeric()
                                        ->minValue(1000)->maxValue(date('Y')),
                                    Forms\Components\TextInput::make('death_year')->numeric()
                                        ->minValue(1000)->maxValue(date('Y')),
                                    Forms\Components\TextInput::make('birth_place'),
                                    Forms\Components\Select::make('country_id')
                                        ->relationship('country', 'name')->searchable()->preload(),
                                ]),
                                Forms\Components\Textarea::make('short_bio')->rows(2)->maxLength(300)
                                    ->helperText('Short bio (max 300 chars) — for listing cards.'),
                                Forms\Components\Toggle::make('is_published')
                                    ->label('Published on public site'),
                            ])
                            ->createOptionModalHeading('Add a new artist'),
                        Forms\Components\TextInput::make('inventory_id')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save (e.g. INV-SIKR-0001)'),
                        Forms\Components\TextInput::make('year_created')->numeric()->minValue(1000)->maxValue(date('Y') + 1),
                        Forms\Components\TextInput::make('year_created_end')->numeric()->label('Year (range end, optional)'),
                        Forms\Components\Select::make('medium_id')
                            ->relationship('medium', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                                Forms\Components\Select::make('parent_id')
                                    ->relationship('parent', 'name')->searchable()->preload()
                                    ->label('Parent medium (optional)'),
                                Forms\Components\TextInput::make('position')->numeric()->default(0),
                                Forms\Components\Textarea::make('description')->rows(2),
                            ])
                            ->createOptionModalHeading('Add a new medium'),
                        Forms\Components\Select::make('genre_id')
                            ->relationship('genre', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                                Forms\Components\Textarea::make('description')->rows(2),
                            ])
                            ->createOptionModalHeading('Add a new genre'),
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'name')
                            ->preload()
                            ->default(1)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                                Forms\Components\ColorPicker::make('color')->default('#999999'),
                                Forms\Components\Toggle::make('is_public')
                                    ->label('Visible on public site')->default(true),
                                Forms\Components\Toggle::make('counts_as_available')
                                    ->label('Counts as available for sale')->default(true),
                                Forms\Components\TextInput::make('position')->numeric()->default(0),
                            ])
                            ->createOptionModalHeading('Add a new status'),
                        Forms\Components\TextInput::make('materials')->columnSpan(2)
                            ->placeholder('e.g. Oil on canvas, mixed media'),
                    ]),
                    Forms\Components\Fieldset::make('Artwork dimensions')
                        ->schema([
                            Forms\Components\TextInput::make('height_cm')->label('Height')->numeric()->suffix('cm'),
                            Forms\Components\TextInput::make('width_cm')->label('Width')->numeric()->suffix('cm'),
                            Forms\Components\TextInput::make('depth_cm')->label('Depth')->numeric()->suffix('cm'),
                            Forms\Components\TextInput::make('weight_kg')->label('Weight')->numeric()->suffix('kg'),
                        ])->columns(4),
                    Forms\Components\Fieldset::make('Frame dimensions')
                        ->schema([
                            Forms\Components\TextInput::make('frame_height_cm')->label('Height')->numeric()->suffix('cm'),
                            Forms\Components\TextInput::make('frame_width_cm')->label('Width')->numeric()->suffix('cm'),
                            Forms\Components\TextInput::make('frame_depth_cm')->label('Depth')->numeric()->suffix('cm'),
                        ])->columns(3),
                    Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
                    Forms\Components\Textarea::make('damage_notes')
                        ->label('Damage / condition notes')
                        ->rows(3)
                        ->columnSpanFull()
                        ->placeholder('e.g. small scratch on left edge, slight discoloration in top right, frame chip…')
                        ->helperText('Visible damage or condition issues — quick reference. For full condition documentation use the History tab.'),
                    Forms\Components\TagsInput::make('tags')
                        ->columnSpanFull()
                        ->placeholder('Press Enter to add a tag')
                        ->helperText('Free-form tags — e.g. abstract, figurative, large-format, university-thesis. Shown as chips on the public artwork page.')
                        ->separator(','),
                ]),

                Forms\Components\Tabs\Tab::make('Edition & Signature')->schema([
                    Forms\Components\Fieldset::make('Edition')->schema([
                        Forms\Components\TextInput::make('edition_number')->numeric(),
                        Forms\Components\TextInput::make('edition_total')->numeric(),
                        Forms\Components\TextInput::make('edition_notes')->placeholder('AP, 1/10, etc.'),
                    ])->columns(3),
                    Forms\Components\Fieldset::make('Signature')->schema([
                        Forms\Components\Toggle::make('is_signed'),
                        Forms\Components\Toggle::make('is_dated'),
                        Forms\Components\Toggle::make('is_framed'),
                        Forms\Components\Textarea::make('signature_description')->columnSpanFull()->rows(2),
                    ])->columns(3),
                ]),

                Forms\Components\Tabs\Tab::make('Pricing')->schema([
                    Forms\Components\Fieldset::make('Asking price')
                        ->schema([
                            Forms\Components\TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->prefix('€')
                                ->step('0.01'),
                            Forms\Components\Select::make('currency')
                                ->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CZK' => 'CZK'])
                                ->default('EUR'),
                            Forms\Components\Toggle::make('price_on_request')->label('Price on request'),
                        ])->columns(3),

                    Forms\Components\Fieldset::make('Acquisition')
                        ->visible(fn (): bool => ! auth()->user()?->isArtist())
                        ->schema([
                            Forms\Components\TextInput::make('purchase_price')
                                ->label('Acquisition cost')
                                ->numeric()
                                ->prefix('€')
                                ->step('0.01')
                                ->helperText('What the gallery / collector paid when the work was acquired.'),
                            Forms\Components\DatePicker::make('purchase_date')
                                ->label('Acquisition date')
                                ->native(false),
                            Forms\Components\TextInput::make('insurance_value')
                                ->label('Insurance value')
                                ->numeric()
                                ->prefix('€')
                                ->step('0.01')
                                ->helperText('Optional — declared value for insurance / loan agreements.'),
                        ])->columns(3),
                ]),

                Forms\Components\Tabs\Tab::make('History')->schema([
                    Forms\Components\Textarea::make('provenance')->rows(3)->columnSpanFull()
                        ->visible(fn (): bool => ! auth()->user()?->isArtist()),
                    Forms\Components\Textarea::make('exhibition_history')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('literature')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('condition_notes')->rows(3)->columnSpanFull(),
                    Forms\Components\Toggle::make('has_certificate_of_authenticity')->label('Has Certificate of Authenticity'),

                    Forms\Components\Fieldset::make('Scanned documents')
                        ->visible(fn (): bool => (bool) (auth()->user()?->isGallery() || auth()->user()?->isCollector() || auth()->user()?->isAdmin()))
                        ->schema([
                            Forms\Components\FileUpload::make('certificate_of_authenticity_document')
                                ->label('Certificate of Authenticity (file)')
                                ->helperText('Upload a scan/PDF of the existing certificate (e.g. issued by the original gallery).')
                                ->disk('public')
                                ->directory('artworks/certificates')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->openable()
                                ->downloadable(),
                            Forms\Components\FileUpload::make('invoice_document')
                                ->label('Invoice (file)')
                                ->helperText('Upload a scan/PDF of the original purchase invoice.')
                                ->disk('public')
                                ->directory('artworks/invoices')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->openable()
                                ->downloadable(),
                        ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make('Location & Images')->schema([
                    Forms\Components\Select::make('location_id')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()->maxLength(255)->columnSpan(2),
                                Forms\Components\Select::make('type')
                                    ->options(\App\Filament\Resources\LocationResource::TYPES)
                                    ->default('storage')->required(),
                                Forms\Components\TextInput::make('city'),
                                Forms\Components\TextInput::make('address_line1')
                                    ->label('Address')->columnSpan(2),
                                Forms\Components\Select::make('country_id')
                                    ->relationship('country', 'name')->searchable()->preload(),
                                Forms\Components\TextInput::make('postal_code'),
                            ]),
                        ])
                        ->createOptionModalHeading('Add a new location'),
                    Forms\Components\FileUpload::make('primary_image')->image()->disk('public')->directory('artworks'),
                    Forms\Components\FileUpload::make('gallery_images')->image()->multiple()->reorderable()->disk('public')->directory('artworks/gallery'),
                ]),

                Forms\Components\Tabs\Tab::make('Maintenance')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->visible(fn (): bool => ! auth()->user()?->isArtist())
                    ->schema([
                        Forms\Components\Repeater::make('maintenances')
                            ->relationship()
                            ->label('Restoration records')
                            ->addActionLabel('+ Add restoration record')
                            ->orderColumn('position')
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): ?string {
                                $date = $state['restoration_date'] ?? null;
                                $name = $state['restorer_name'] ?? null;
                                $returned = $state['restoration_returned_at'] ?? null;
                                $status = $returned ? '✅ Returned' : '🔧 In restoration';
                                $bits = array_filter([
                                    $date ? \Illuminate\Support\Carbon::parse($date)->format('d. m. Y') : null,
                                    $name,
                                ]);
                                return $bits ? $status.' · '.implode(' · ', $bits) : $status;
                            })
                            ->schema([
                                Forms\Components\Fieldset::make('Restoration')
                                    ->schema([
                                        Forms\Components\DatePicker::make('restoration_date')
                                            ->label('Date sent to restoration')
                                            ->native(false),
                                        Forms\Components\DatePicker::make('restoration_returned_at')
                                            ->label('Date returned')
                                            ->native(false)
                                            ->helperText('Leave empty if still in restoration.'),
                                        Forms\Components\TextInput::make('restoration_price')
                                            ->label('Restoration cost')
                                            ->numeric()
                                            ->prefix('€')
                                            ->step('0.01'),
                                        Forms\Components\Textarea::make('restoration_notes')
                                            ->label('Restoration notes')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('What was restored, scope of work, materials used, observations…'),
                                    ])->columns(2),

                                Forms\Components\Fieldset::make('Restorer contact')
                                    ->schema([
                                        Forms\Components\TextInput::make('restorer_name')
                                            ->label('Name / studio')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('restorer_email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('restorer_phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ])->columns(2),

                                Forms\Components\FileUpload::make('documents')
                                    ->label('Documents')
                                    ->helperText('Reports, invoices, certificates — PDF / DOC / images.')
                                    ->multiple()
                                    ->reorderable()
                                    ->downloadable()
                                    ->openable()
                                    ->disk('public')
                                    ->directory('artworks/restoration/documents')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'image/jpeg',
                                        'image/png',
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('photos')
                                    ->label('Photos')
                                    ->helperText('Before / after / in-progress photos.')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->openable()
                                    ->disk('public')
                                    ->directory('artworks/restoration/photos')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Tabs\Tab::make('Publishing')->schema([
                    Forms\Components\Toggle::make('is_published')->label('Published on public site'),
                    Forms\Components\Toggle::make('is_featured')->label('Featured (show on homepage)'),
                ]),

                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primary_image')->disk('public')->square()->size(50),
                Tables\Columns\TextColumn::make('inventory_id')->searchable()->sortable()->copyable()->fontFamily('mono')->size('xs'),
                Tables\Columns\TextColumn::make('artist.last_name')
                    ->label('Artist')
                    ->formatStateUsing(fn ($record): string => $record->artist?->display_name ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('artist', function ($q) use ($search) {
                            $needle = '%'.$search.'%';
                            $q->where('first_name', 'ilike', $needle)
                              ->orWhere('last_name', 'ilike', $needle);
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('year_created')->sortable(),
                Tables\Columns\TextColumn::make('medium.name')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->color(fn ($state, $record) => $record->status?->color ?? 'gray'),
                Tables\Columns\TextColumn::make('price')
                    ->money(fn ($record) => $record->currency ?? 'EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Public'),
                Tables\Columns\TextColumn::make('maintenance_status')
                    ->label('Maintenance')
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
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist')
                    ->relationship('artist', 'last_name', fn ($query) => $query->orderBy('last_name')->orderBy('first_name'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('medium')->relationship('medium', 'name'),
                Tables\Filters\SelectFilter::make('status')->relationship('status', 'name'),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-m-document-duplicate')
                    ->action(function (Artwork $record, $livewire): void {
                        $copy = $record->replicate(['uuid', 'inventory_id', 'slug', 'created_at', 'updated_at', 'deleted_at']);
                        $copy->title = $record->title.' (copy)';
                        $copy->is_published = false;
                        $copy->save();
                        Notification::make()
                            ->title('Artwork duplicated as '.$copy->inventory_id)
                            ->success()->send();
                        $livewire->redirect(self::getUrl('edit', ['record' => $copy]));
                    }),
                Tables\Actions\Action::make('printCard')
                    ->label('Print artwork card')
                    ->icon('heroicon-m-printer')
                    ->url(fn (Artwork $record): string => route('artworks.print.card', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('printCertificate')
                    ->label('Print certificate')
                    ->icon('heroicon-m-shield-check')
                    ->visible(fn (): bool => ! auth()->user()?->isCollector())
                    ->url(fn (Artwork $record): string => route('artworks.print.certificate', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('printLabel')
                    ->label('Print label')
                    ->icon('heroicon-m-tag')
                    ->url(fn (Artwork $record): string => route('artworks.print.label', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('printMaintenance')
                    ->label('Print maintenance report')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->visible(fn (): bool => ! auth()->user()?->isArtist())
                    ->url(fn (Artwork $record): string => route('artworks.print.maintenance', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('pdfCard')
                    ->label('Download card (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Artwork $record): string => route('artworks.pdf.card', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('pdfCertificate')
                    ->label('Download certificate (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (): bool => ! auth()->user()?->isCollector())
                    ->url(fn (Artwork $record): string => route('artworks.pdf.certificate', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('pdfLabel')
                    ->label('Download label (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Artwork $record): string => route('artworks.pdf.label', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('pdfMaintenance')
                    ->label('Download maintenance report (PDF)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (): bool => ! auth()->user()?->isArtist())
                    ->url(fn (Artwork $record): string => route('artworks.pdf.maintenance', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('enterSale')
                    ->label('Enter sale')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->modalHeading('Enter a sale')
                    ->modalSubmitActionLabel('Create sale')
                    ->fillForm(fn (Artwork $record): array => [
                        'unit_price' => $record->price,
                        'base_price' => $record->price,
                    ])
                    ->form([
                        Forms\Components\Select::make('buyer_contact_id')
                            ->label('Buyer')
                            ->options(fn (): array => Contact::query()
                                ->orderBy('last_name')->orderBy('first_name')->get()
                                ->mapWithKeys(fn (Contact $c) => [$c->id => $c->display_name])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Contact::find($value)?->display_name)
                            ->searchable()
                            ->placeholder('Select a buyer (optional)')
                            ->visible(fn (Forms\Get $get): bool => ! $get('create_new_buyer')),
                        Forms\Components\Toggle::make('create_new_buyer')
                            ->label('Buyer is not in the list — add a new one')
                            ->live(),
                        Forms\Components\Fieldset::make('New buyer')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('create_new_buyer'))
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('new_first_name')
                                    ->label('First name')->maxLength(255),
                                Forms\Components\TextInput::make('new_last_name')
                                    ->label('Last name')->maxLength(255)
                                    ->required(fn (Forms\Get $get): bool => (bool) $get('create_new_buyer')),
                                Forms\Components\TextInput::make('new_organization')
                                    ->label('Organization')->maxLength(255)->columnSpan(2),
                                Forms\Components\TextInput::make('new_email')
                                    ->label('Email')->email()->maxLength(255),
                                Forms\Components\TextInput::make('new_phone')
                                    ->label('Phone')->tel()->maxLength(255),
                            ]),
                        Forms\Components\DatePicker::make('sale_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Hidden::make('base_price'),
                        Forms\Components\ToggleButtons::make('discount_percent')
                            ->label('Discount')
                            ->options([
                                0  => 'Full price',
                                10 => '-10 %',
                                15 => '-15 %',
                                20 => '-20 %',
                            ])
                            ->colors([
                                0  => 'gray',
                                10 => 'warning',
                                15 => 'warning',
                                20 => 'danger',
                            ])
                            ->default(0)
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                $base = (float) $get('base_price');
                                $set('unit_price', round($base * (1 - ((int) $state) / 100), 2));
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->helperText('Pre-filled from the artwork price — pick a discount above or adjust manually.'),
                    ])
                    ->action(function (Artwork $record, array $data, $livewire): void {
                        $buyerId = $data['buyer_contact_id'] ?? null;

                        if (! empty($data['create_new_buyer'])) {
                            $buyerId = Contact::create([
                                'first_name'   => $data['new_first_name'] ?? null,
                                'last_name'    => $data['new_last_name'] ?? null,
                                'organization' => $data['new_organization'] ?? null,
                                'email'        => $data['new_email'] ?? null,
                                'phone'        => $data['new_phone'] ?? null,
                            ])->getKey();
                        }

                        $sale = Sale::create([
                            'buyer_contact_id' => $buyerId,
                            'sale_date'        => $data['sale_date'],
                            'currency'         => $record->currency ?: 'EUR',
                            'payment_status'   => 'draft',
                        ]);

                        $sale->lineItems()->create([
                            'artwork_id'  => $record->getKey(),
                            'description' => trim($record->title.' — '.($record->artist?->display_name ?? '')),
                            'quantity'    => 1,
                            'unit_price'  => $data['unit_price'] ?? 0,
                            'position'    => 0,
                        ]);

                        $sale->recalculateTotals();

                        Notification::make()
                            ->title('Sale '.$sale->invoice_number.' created')
                            ->success()
                            ->send();

                        $livewire->redirect(SaleResource::getUrl('edit', ['record' => $sale]));
                    }),
                Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportCatalogue')
                        ->label('Catalogue (PDF)')
                        ->icon('heroicon-o-book-open')
                        ->color('gray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $artworks = $records->load(['artist', 'medium', 'genre']);
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.artwork-catalogue-pdf', [
                                'artworks' => $artworks,
                                'settings' => \App\Models\InvoiceSetting::current(),
                            ])->setPaper('a4');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'artwork-catalogue-'.now()->format('Y-m-d').'-'.$records->count().'works.pdf',
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('exportCertificates')
                        ->label('Certificates (PDF)')
                        ->icon('heroicon-o-shield-check')
                        ->color('gray')
                        ->visible(fn (): bool => ! auth()->user()?->isCollector())
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $artworks = $records->load(['artist', 'medium']);
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.artwork-certificates-pdf', [
                                'artworks' => $artworks,
                                'settings' => \App\Models\InvoiceSetting::current(),
                            ])->setPaper('a4');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'artwork-certificates-'.now()->format('Y-m-d').'-'.$records->count().'works.pdf',
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('exportLabels')
                        ->label('Labels (PDF)')
                        ->icon('heroicon-o-tag')
                        ->color('gray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $artworks = $records->load(['artist', 'medium']);
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.artwork-labels-pdf', [
                                'artworks' => $artworks,
                                'settings' => \App\Models\InvoiceSetting::current(),
                            ])->setPaper('a4');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'artwork-labels-'.now()->format('Y-m-d').'-'.$records->count().'works.pdf',
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        // Artist vidí v admine len svoje diela.
        if ($user?->isArtist()) {
            $query->where('owner_user_id', $user->id);
        }

        // Gallery vidí len artworks tých artistov, ktorých zastupuje.
        if ($user?->isGallery() && $user->gallery) {
            $query->whereHas('artist.galleries', fn ($q) => $q->whereKey($user->gallery->id));
        }

        // Collector v admine vidí IBA svoju súkromnú databázu (own records).
        if ($user?->isCollector()) {
            $query->where('owner_user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArtworks::route('/'),
            'create' => Pages\CreateArtwork::route('/create'),
            'edit'   => Pages\EditArtwork::route('/{record}/edit'),
        ];
    }
}
