<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionResource\Pages;
use App\Models\Artwork;
use App\Models\Collection;
use App\Models\Genre;
use App\Models\Medium;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'title')
                ->searchable()
                ->preload()
                ->label('Parent collection (optional)'),
            Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_image')->image()->disk('public')->directory('collections'),
            Forms\Components\Toggle::make('is_public')->default(true),
            Forms\Components\TextInput::make('position')->numeric()->default(0),

            Forms\Components\Section::make('Build from criteria')
                ->description('Pick a medium, genre and/or material — matching artworks fill in below automatically.')
                ->icon('heroicon-o-funnel')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('filter_medium_id')
                            ->label('Medium')
                            ->options(fn (): array => Medium::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::applyArtworkCriteria($get, $set)),
                        Forms\Components\Select::make('filter_genre_id')
                            ->label('Genre')
                            ->options(fn (): array => Genre::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::applyArtworkCriteria($get, $set)),
                        Forms\Components\TextInput::make('filter_material')
                            ->label('Material contains')
                            ->placeholder('e.g. canvas, bronze')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::applyArtworkCriteria($get, $set)),
                    ]),
                ])
                ->columnSpanFull()
                ->collapsible(),

            Forms\Components\Section::make('Artworks in this collection')
                ->schema([
                    Forms\Components\Select::make('artworks')
                        ->relationship('artworks', 'title')
                        ->multiple()
                        ->searchable(['title', 'inventory_id'])
                        ->preload()
                        ->allowHtml()
                        ->getOptionLabelFromRecordUsing(fn (Artwork $record): string => self::artworkOptionLabel($record))
                        ->helperText('Each option shows a thumbnail, the artist and the title for quick identification.'),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Naplní výber diel ('artworks') dielami, ktoré vyhovujú zvoleným kritériám
     * (medium / genre / material). Volá sa živo pri zmene ktoréhokoľvek filtra.
     */
    protected static function applyArtworkCriteria(Forms\Get $get, Forms\Set $set): void
    {
        $query = Artwork::query();
        $hasCriteria = false;

        if ($get('filter_medium_id')) {
            $query->where('medium_id', $get('filter_medium_id'));
            $hasCriteria = true;
        }
        if ($get('filter_genre_id')) {
            $query->where('genre_id', $get('filter_genre_id'));
            $hasCriteria = true;
        }
        if (filled($get('filter_material'))) {
            $query->where('materials', 'ilike', '%'.trim($get('filter_material')).'%');
            $hasCriteria = true;
        }

        if (! $hasCriteria) {
            return;
        }

        $set('artworks', $query->pluck('id')->all());
    }

    /**
     * HTML menovka diela pre Select — náhľad fotky + autor, názov a inventárne číslo,
     * aby sa diela dali rýchlo identifikovať.
     */
    protected static function artworkOptionLabel(Artwork $record): string
    {
        $image = $record->primary_image
            ? '<img src="'.e(Storage::url($record->primary_image)).'" alt="" '
                .'style="width:2.75rem;height:2.75rem;border-radius:0.375rem;object-fit:cover;flex:none;">'
            : '<span style="width:2.75rem;height:2.75rem;border-radius:0.375rem;flex:none;'
                .'background:rgba(120,120,130,0.25);display:flex;align-items:center;'
                .'justify-content:center;font-size:0.85rem;opacity:0.6;">&#9711;</span>';

        $title  = e($record->title);
        $artist = e($record->artist?->display_name ?? 'Unknown artist');
        $inv    = e($record->inventory_id);

        return '<div style="display:flex;align-items:center;gap:0.625rem;">'
            .$image
            .'<span style="line-height:1.25;">'
            .'<span style="font-weight:600;">'.$title.'</span><br>'
            .'<span style="font-size:0.75rem;opacity:0.7;">'.$artist.' &middot; '.$inv.'</span>'
            .'</span></div>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()->sortable()
                    ->weight('medium')
                    ->description(fn (Collection $r): ?string => $r->parent?->title
                        ? '↳ in '.$r->parent->title
                        : null),
                Tables\Columns\TextColumn::make('artworks_count')
                    ->counts('artworks')
                    ->label('Works')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('artwork_previews')
                    ->label('Preview')
                    ->disk('public')
                    ->stacked()
                    ->limit(8)
                    ->limitedRemainingText()
                    ->square()
                    ->size(42),
                Tables\Columns\TextColumn::make('artworks_total_value')
                    ->label('Total value')
                    ->state(fn (Collection $r) => $r->artworks_total_value)
                    ->money('EUR')
                    ->placeholder('—')
                    ->alignEnd()
                    ->sortable(false),
                Tables\Columns\IconColumn::make('is_public')->boolean()->label('Public'),
                Tables\Columns\TextColumn::make('owner.email')->label('Owner')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->dateTime()->since()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_public'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('printCatalogue')
                        ->label('Catalogue (PDF)')
                        ->icon('heroicon-m-book-open')
                        ->action(function (Collection $record) {
                            $artworks = $record->artworks()->with(['artist', 'medium', 'genre'])->get();
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.artwork-catalogue-pdf', [
                                'artworks' => $artworks,
                                'settings' => \App\Models\InvoiceSetting::current(),
                            ])->setPaper('a4');
                            $name = 'catalogue-'.\Illuminate\Support\Str::slug($record->title).'-'.now()->format('Ymd').'.pdf';
                            return response()->streamDownload(fn () => print($pdf->output()), $name);
                        }),

                    Tables\Actions\Action::make('printCards')
                        ->label('Artwork cards (PDF)')
                        ->icon('heroicon-m-document-text')
                        ->action(function (Collection $record) {
                            $artworks = $record->artworks()->with(['artist', 'medium', 'genre'])->get();
                            $settings = \App\Models\InvoiceSetting::current();
                            $size = match ($settings->card_size ?? 'a4') {
                                'a5' => 'a5', 'letter' => 'letter', default => 'a4',
                            };
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.collection-cards-pdf', [
                                'artworks' => $artworks,
                                'settings' => $settings,
                            ])->setPaper($size);
                            $name = 'cards-'.\Illuminate\Support\Str::slug($record->title).'-'.now()->format('Ymd').'.pdf';
                            return response()->streamDownload(fn () => print($pdf->output()), $name);
                        }),

                    Tables\Actions\Action::make('printLabels')
                        ->label('Artwork labels (PDF)')
                        ->icon('heroicon-m-tag')
                        ->action(function (Collection $record) {
                            $artworks = $record->artworks()->with(['artist', 'medium'])->get();
                            $settings = \App\Models\InvoiceSetting::current();
                            $size = match ($settings->label_size ?? 'standard') {
                                'small'    => [0, 0, 60  * 2.834, 40  * 2.834],
                                'large'    => [0, 0, 105 * 2.834, 70  * 2.834],
                                'a6'       => 'a6',
                                default    => [0, 0, 85  * 2.834, 55  * 2.834],
                            };
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('prints.artwork-labels-pdf', [
                                'artworks' => $artworks,
                                'settings' => $settings,
                            ])->setPaper($size, 'landscape');
                            $name = 'labels-'.\Illuminate\Support\Str::slug($record->title).'-'.now()->format('Ymd').'.pdf';
                            return response()->streamDownload(fn () => print($pdf->output()), $name);
                        }),

                    Tables\Actions\Action::make('exportCsv')
                        ->label('Export CSV')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->action(function (Collection $record) {
                            $name = 'collection-'.\Illuminate\Support\Str::slug($record->title).'-'.now()->format('Ymd').'.csv';
                            return response()->streamDownload(function () use ($record) {
                                $h = fopen('php://output', 'w');
                                fputcsv($h, ['Inv. ID', 'Title', 'Artist', 'Year', 'Medium', 'Price', 'Currency', 'Published']);
                                foreach ($record->artworks()->with(['artist', 'medium'])->get() as $a) {
                                    fputcsv($h, [
                                        $a->inventory_id,
                                        $a->title,
                                        $a->artist?->display_name ?? '',
                                        $a->year_created,
                                        $a->medium?->name ?? '',
                                        $a->price,
                                        $a->currency,
                                        $a->is_published ? 'yes' : 'no',
                                    ]);
                                }
                                fclose($h);
                            }, $name, ['Content-Type' => 'text/csv']);
                        }),

                    Tables\Actions\Action::make('bulkAddArtworks')
                        ->label('Bulk add artworks')
                        ->icon('heroicon-m-plus-circle')
                        ->form([
                            Forms\Components\Select::make('artwork_ids')
                                ->label('Pick artworks to add')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(function (Collection $record) {
                                    $existing = $record->artworks()->pluck('artworks.id')->all();
                                    return Artwork::query()
                                        ->whereNotIn('id', $existing)
                                        ->orderBy('title')
                                        ->limit(500)
                                        ->get()
                                        ->mapWithKeys(fn (Artwork $a) => [
                                            $a->id => trim(($a->inventory_id ? '['.$a->inventory_id.'] ' : '').$a->title.' — '.($a->artist?->display_name ?? '')),
                                        ])
                                        ->all();
                                })
                                ->required(),
                        ])
                        ->action(function (Collection $record, array $data) {
                            $record->artworks()->syncWithoutDetaching($data['artwork_ids'] ?? []);
                        }),

                    Tables\Actions\Action::make('archive')
                        ->label('Archive collection')
                        ->icon('heroicon-m-archive-box-arrow-down')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Marks the collection as archived (soft delete). You can restore it from the Trashed filter.')
                        ->action(fn (Collection $record) => $record->delete())
                        ->visible(fn (Collection $record): bool => $record->deleted_at === null),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->defaultSort('position');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // Artist a Collector vidia v admine iba vlastné kolekcie.
        if ($user && ($user->isArtist() || $user->isCollector())) {
            $query->where('owner_user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit'   => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
