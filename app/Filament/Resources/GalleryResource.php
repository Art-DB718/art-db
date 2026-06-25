<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryResource\Pages;
use App\Models\Gallery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?int $navigationSort = 0;
    protected static ?string $recordTitleAttribute = 'name';

    /** Only Gallery users see this in the menu — labeled "My gallery". */
    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && ($u->isAdmin() || $u->isGallery());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isGallery() ? 'My gallery' : 'Galleries';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-building-storefront';
    }

    /** Gallery user jumps straight to their own gallery edit (or create). */
    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        if ($user?->isGallery()) {
            $gallery = $user->gallery;
            return $gallery
                ? static::getUrl('edit', ['record' => $gallery])
                : static::getUrl('create');
        }

        return static::getUrl('index');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();
        if ($user?->isGallery()) {
            $query->where('owner_user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->persistTabInQueryString('tab')->tabs([

                Forms\Components\Tabs\Tab::make('Profile')->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('About the gallery — programme, focus, history.'),
                    Forms\Components\Fieldset::make('Branding')->schema([
                        Forms\Components\FileUpload::make('logo')->image()->disk('public')->directory('galleries/logos'),
                        Forms\Components\FileUpload::make('cover_image')->image()->disk('public')->directory('galleries/covers'),
                    ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make('Contact')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('email')->email()->maxLength(255),
                        Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
                        Forms\Components\TextInput::make('website')->url()->prefix('https://')->maxLength(255)->columnSpan(2),
                    ]),
                    Forms\Components\Fieldset::make('Address')->schema([
                        Forms\Components\TextInput::make('address_line1')->label('Street')->maxLength(255)->columnSpan(2),
                        Forms\Components\TextInput::make('address_line2')->label('Street 2 (optional)')->maxLength(255)->columnSpan(2),
                        Forms\Components\TextInput::make('city')->maxLength(120),
                        Forms\Components\TextInput::make('postal_code')->maxLength(20),
                        Forms\Components\Select::make('country_id')->relationship('country', 'name')->searchable()->preload(),
                    ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make('Represented artists')->schema([
                    Forms\Components\Placeholder::make('artists_hint')
                        ->label('')
                        ->content('Add artists from the Artists section (Gallery users see "+ Represent artist" action there).'),
                ]),

                Forms\Components\Tabs\Tab::make('Publishing')->schema([
                    Forms\Components\Toggle::make('is_published')->label('Published in public archive'),
                ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->disk('public')->circular()->size(40),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->toggleable(),
                Tables\Columns\TextColumn::make('artists_count')->counts('artists')->label('Artists')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Public'),
                Tables\Columns\TextColumn::make('owner.email')->label('Owner')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit'   => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
