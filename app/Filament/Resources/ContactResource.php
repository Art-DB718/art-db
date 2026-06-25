<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'last_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('first_name')->maxLength(255),
                    Forms\Components\TextInput::make('last_name')->maxLength(255),
                    Forms\Components\TextInput::make('organization')->maxLength(255)->columnSpan(2),
                    Forms\Components\TextInput::make('email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
                    Forms\Components\Select::make('group_id')
                        ->relationship('group', 'name')->searchable()->preload()->label('Contact group'),
                    Forms\Components\Select::make('source')
                        ->options([
                            'website'     => 'Website',
                            'exhibition'  => 'Exhibition',
                            'referral'    => 'Referral',
                            'art_fair'    => 'Art fair',
                            'newsletter'  => 'Newsletter',
                            'other'       => 'Other',
                        ])
                        ->native(false),
                ]),
            ]),

            Forms\Components\Section::make('Address')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('address_line1')->label('Address line 1')->columnSpan(2),
                    Forms\Components\TextInput::make('address_line2')->label('Address line 2')->columnSpan(2),
                    Forms\Components\TextInput::make('city'),
                    Forms\Components\TextInput::make('postal_code'),
                    Forms\Components\Select::make('country_id')
                        ->relationship('country', 'name')->searchable()->preload(),
                ]),
            ])->collapsed(),

            Forms\Components\Section::make('CRM')->schema([
                Forms\Components\TagsInput::make('interests')
                    ->placeholder('painting, sculpture, …')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('last_contact_at'),
                Forms\Components\Toggle::make('subscribed_to_newsletter')->label('Subscribed to newsletter'),
                Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('organization')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable()->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('group.name')->label('Group')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('country.name')->label('Country')->toggleable(),
                Tables\Columns\IconColumn::make('subscribed_to_newsletter')->boolean()->label('Newsletter'),
                Tables\Columns\TextColumn::make('sales_count')->counts('sales')->label('Sales'),
            ])
            ->defaultSort('last_name')
            ->filters([
                Tables\Filters\SelectFilter::make('group')->relationship('group', 'name'),
                Tables\Filters\SelectFilter::make('country')->relationship('country', 'name'),
                Tables\Filters\TernaryFilter::make('subscribed_to_newsletter')->label('Newsletter'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('sendPrivateRoom')
                        ->label('Send Private Room')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('gray')
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
                        ->action(function (Contact $record, array $data) {
                            $room = \App\Models\PrivateRoom::find($data['private_room_id']);
                            if (! $room) return;

                            // Attach contact ako recipient (pivot)
                            $room->recipients()->syncWithoutDetaching([
                                $record->id => ['status' => 'sent', 'sent_at' => now()],
                            ]);

                            $room->forceFill(['sent_at' => now()])->save();

                            // Mail recipientovi
                            $url = $room->publicUrl();
                            $greeting = trim('Dear '.($record->first_name ?: $record->display_name));
                            $html = '<p>'.e($greeting).',</p>'
                                .($data['note'] ?? null
                                    ? '<p>'.nl2br(e($data['note'])).'</p>'
                                    : '')
                                .'<p>You are invited to a private viewing: <strong>'.e($room->title).'</strong>.</p>'
                                .'<p><a href="'.e($url).'" style="display:inline-block;padding:12px 24px;background:#111827;color:#fff;text-decoration:none;letter-spacing:0.1em;text-transform:uppercase;font-size:0.75rem;">Open private viewing</a></p>'
                                .'<p style="color:#6b7280">Or copy this link: '.e($url).'</p>';

                            try {
                                \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($record, $room) {
                                    $m->to($record->email)
                                      ->subject('Private viewing: '.$room->title);
                                });
                                \Filament\Notifications\Notification::make()
                                    ->title('Private Room sent')
                                    ->body('Sent to '.$record->email)
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
        return [
            RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
