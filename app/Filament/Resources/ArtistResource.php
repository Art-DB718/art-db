<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'last_name';

    /** Artist user sees "About me", everyone else sees "Artists". */
    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isArtist() ? 'About me' : 'Artists';
    }

    public static function getNavigationIcon(): string
    {
        return auth()->user()?->isArtist() ? 'heroicon-o-identification' : 'heroicon-o-user-circle';
    }

    /** Artist user jumps straight to their own profile edit (or create if none). */
    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        if ($user?->isArtist()) {
            $artist = $user->artistProfile;
            return $artist
                ? static::getUrl('edit', ['record' => $artist])
                : static::getUrl('create');
        }

        return static::getUrl('index');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([

                Forms\Components\Tabs\Tab::make('Basic')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('first_name')->required(),
                        Forms\Components\TextInput::make('last_name')->required(),
                        Forms\Components\TextInput::make('birth_year')
                            ->label('Year of birth')
                            ->numeric()
                            ->rule('integer')
                            ->step(1)
                            ->minValue(1000)
                            ->maxValue((int) date('Y'))
                            ->placeholder('e.g. 1955')
                            ->helperText('Year only, 4 digits — not a full date.'),
                        Forms\Components\TextInput::make('death_year')
                            ->label('Year of death')
                            ->numeric()
                            ->rule('integer')
                            ->step(1)
                            ->minValue(1000)
                            ->maxValue((int) date('Y'))
                            ->placeholder('e.g. 2010')
                            ->helperText('Leave empty for living artists.')
                            ->visible(fn (): bool => ! auth()->user()?->isArtist()),
                        Forms\Components\TextInput::make('birth_place'),
                        Forms\Components\Select::make('country_id')->relationship('country', 'name')->searchable()->preload(),
                    ]),
                    Forms\Components\Textarea::make('short_bio')->rows(2)->maxLength(300)
                        ->helperText('Krátky bio (max 300 znakov) — pre listing kartu'),
                ]),

                Forms\Components\Tabs\Tab::make('Bio & CV')->schema([
                    Forms\Components\Textarea::make('biography')
                        ->label('Biography')
                        ->rows(10)
                        ->columnSpanFull()
                        ->helperText('Long-form biographical text — life story, key milestones, influences.'),

                    Forms\Components\Textarea::make('statement')
                        ->label('Artist Statement')
                        ->rows(6)
                        ->columnSpanFull()
                        ->helperText('First-person reflection on the practice — themes, methods, intent.'),

                    Forms\Components\Repeater::make('education')
                        ->label('Education / Studies')
                        ->addActionLabel('+ Add education record')
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->defaultItems(0)
                        ->itemLabel(function (array $state): ?string {
                            $bits = array_filter([
                                $state['institution'] ?? null,
                                $state['degree'] ?? null,
                                trim(($state['year_from'] ?? '').'–'.($state['year_to'] ?? ''), '–'),
                            ]);
                            return $bits ? implode(' · ', $bits) : 'New education record';
                        })
                        ->schema([
                            Forms\Components\TextInput::make('institution')
                                ->label('University / school')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->placeholder('e.g. Vysoká škola výtvarných umení v Bratislave'),
                            Forms\Components\TextInput::make('city')->maxLength(120),
                            Forms\Components\TextInput::make('country')->label('Country')->maxLength(120),
                            Forms\Components\Select::make('degree')
                                ->options([
                                    'BA'      => 'BA (Bachelor)',
                                    'MA'      => 'MA (Master)',
                                    'MFA'     => 'MFA',
                                    'PhD'     => 'PhD',
                                    'Diploma' => 'Diploma',
                                    'Other'   => 'Other',
                                ])
                                ->native(false),
                            Forms\Components\TextInput::make('field')
                                ->label('Field of study')
                                ->placeholder('Painting, Sculpture, Photography…')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('year_from')->label('From year')
                                ->numeric()->rule('integer')->step(1)
                                ->minValue(1900)->maxValue((int) date('Y') + 6)
                                ->placeholder('e.g. 2010'),
                            Forms\Components\TextInput::make('year_to')->label('To year')
                                ->numeric()->rule('integer')->step(1)
                                ->minValue(1900)->maxValue((int) date('Y') + 6)
                                ->placeholder('e.g. 2015')
                                ->helperText('Leave empty if still studying.'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('previous_exhibitions')
                        ->label('Previous exhibitions')
                        ->addActionLabel('+ Add exhibition')
                        ->collapsible()
                        ->collapsed()
                        ->reorderable()
                        ->defaultItems(0)
                        ->itemLabel(function (array $state): ?string {
                            $bits = array_filter([
                                $state['year'] ?? null,
                                $state['title'] ?? null,
                                $state['venue'] ?? null,
                            ]);
                            return $bits ? implode(' · ', $bits) : 'New exhibition';
                        })
                        ->schema([
                            Forms\Components\TextInput::make('year')
                                ->numeric()->rule('integer')->step(1)
                                ->minValue(1900)->maxValue((int) date('Y') + 1)
                                ->placeholder('e.g. 2024')
                                ->required(),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'solo'  => 'Solo show',
                                    'group' => 'Group show',
                                    'duo'   => 'Duo show',
                                    'biennale' => 'Biennale / festival',
                                    'art_fair' => 'Art fair',
                                ])
                                ->default('group')
                                ->native(false),
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->placeholder('e.g. "Inner Landscapes"'),
                            Forms\Components\TextInput::make('venue')
                                ->maxLength(255)
                                ->placeholder('e.g. Schottert Contemporary, SNG, MoMA PS1…'),
                            Forms\Components\TextInput::make('city')->maxLength(120),
                            Forms\Components\TextInput::make('country')->maxLength(120),
                            Forms\Components\TextInput::make('url')
                                ->label('Reference URL (optional)')
                                ->url()
                                ->maxLength(255)
                                ->columnSpan(2),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

                Forms\Components\Tabs\Tab::make('Contact & Web')->schema([
                    Forms\Components\TextInput::make('website')->url()->prefix('https://'),
                    Forms\Components\KeyValue::make('social_links')
                        ->keyLabel('Platform (instagram, facebook, ...)')
                        ->valueLabel('URL')
                        ->reorderable(),
                ]),

                Forms\Components\Tabs\Tab::make('Images')->schema([
                    Forms\Components\FileUpload::make('profile_image')
                        ->label('Portrait image')
                        ->image()->disk('public')->directory('artists/profile'),
                    Forms\Components\FileUpload::make('cover_image')
                        ->label('Signature image')
                        ->image()->disk('public')->directory('artists/signatures'),
                ]),

                Forms\Components\Tabs\Tab::make('Publishing')->schema([
                    Forms\Components\Toggle::make('is_published')->label('Published on public site'),
                    Forms\Components\Toggle::make('is_featured')->label('Featured (homepage)'),
                    Forms\Components\Select::make('branding_theme')
                        ->options(['default' => 'Default', 'minimal' => 'Minimal', 'editorial' => 'Editorial', 'classic' => 'Classic'])
                        ->default('default'),
                ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_image')->disk('public')->circular()->size(40),
                Tables\Columns\TextColumn::make('display_name')->label('Name')->searchable(['first_name', 'last_name'])->sortable(),
                Tables\Columns\TextColumn::make('birth_year')->sortable(),
                Tables\Columns\TextColumn::make('life_status')
                    ->label('Úmrtie')
                    ->badge()
                    ->state(fn (Artist $record): string => $record->death_year
                        ? '† '.$record->death_year
                        : 'Living')
                    ->color(fn (Artist $record): string => $record->death_year ? 'gray' : 'success')
                    ->icon(fn (Artist $record): string => $record->death_year
                        ? 'heroicon-m-archive-box'
                        : 'heroicon-m-check-badge')
                    ->sortable(['death_year']),
                Tables\Columns\TextColumn::make('country.name')->label('Country'),
                Tables\Columns\TextColumn::make('artworks_count')->counts('artworks')->label('Artworks'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Public'),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
                Tables\Columns\ToggleColumn::make('represented_by_me')
                    ->label('Represented')
                    ->visible(fn (): bool => auth()->user()?->isGallery() === true && auth()->user()->gallery !== null)
                    ->getStateUsing(function (Artist $record): bool {
                        $gallery = auth()->user()->gallery;
                        return $gallery
                            ? $record->galleries()->whereKey($gallery->id)->exists()
                            : false;
                    })
                    ->updateStateUsing(function (Artist $record, bool $state): bool {
                        $gallery = auth()->user()->gallery;
                        if (! $gallery) return false;
                        if ($state) {
                            $gallery->artists()->syncWithoutDetaching([
                                $record->id => ['represented_since' => now()->toDateString()],
                            ]);
                        } else {
                            $gallery->artists()->detach($record->id);
                        }
                        return $state;
                    })
                    ->tooltip('Toggle whether your gallery represents this artist.'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('represented_by_me')
                    ->label('Only artists I represent')
                    ->visible(fn (): bool => auth()->user()?->isGallery() === true && auth()->user()->gallery !== null)
                    ->query(function ($query) {
                        $gallery = auth()->user()->gallery;
                        return $gallery
                            ? $query->whereHas('galleries', fn ($q) => $q->whereKey($gallery->id))
                            : $query;
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        // Artist / Collector: strict own-ownership workspace.
        if ($user && ($user->isArtist() || $user->isCollector())) {
            $query->where('owner_user_id', $user->id);
        }

        // Gallery: own-created artists PLUS featured artists — anyone whose
        // work the gallery has uploaded, so 'Featured artists' from public
        // /galleries/{slug} shows up here too and Kat can edit their metadata
        // if needed.
        if ($user?->isGallery()) {
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('owner_user_id', $userId)
                  ->orWhereIn('id', function ($sub) use ($userId) {
                      $sub->select('artist_id')
                          ->from('artworks')
                          ->where('owner_user_id', $userId)
                          ->whereNotNull('artist_id')
                          ->distinct();
                  });
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArtists::route('/'),
            'create' => Pages\CreateArtist::route('/create'),
            'edit'   => Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
