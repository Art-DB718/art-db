<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

/**
 * Admin-only resource for managing user accounts. Not visible to
 * Gallery / Artist / Collector users — see canViewAny() + policy.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static ?string $recordTitleAttribute = 'email';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()->required()->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('role')
                        ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all())
                        ->required()
                        ->native(false),
                    Forms\Components\DateTimePicker::make('email_verified_at')
                        ->label('Verified at')
                        ->native(false),
                ]),
                Forms\Components\TextInput::make('password')
                    ->password()->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255)
                    ->helperText('Leave blank to keep the current password unchanged.'),
            ]),

            Forms\Components\Section::make('Institution (optional)')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('institution_name')->maxLength(255),
                    Forms\Components\TextInput::make('institution_website')->url()->maxLength(255),
                    Forms\Components\TextInput::make('institution_city')->maxLength(255),
                    Forms\Components\TextInput::make('institution_country')->maxLength(255),
                ]),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable()
                    ->copyable()->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?UserRole $state) => $state?->label() ?? '—')
                    ->color(fn (?UserRole $state) => match ($state) {
                        UserRole::Admin     => 'danger',
                        UserRole::Gallery   => 'success',
                        UserRole::Artist    => 'info',
                        UserRole::Collector => 'warning',
                        default             => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->email_verified_at))
                    ->sortable(),
                Tables\Columns\TextColumn::make('onboarded_at')->label('Onboarded')->date('d.m.Y')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Registered')->date('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
