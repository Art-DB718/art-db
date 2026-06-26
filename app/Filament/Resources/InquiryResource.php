<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int    $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'id';

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) return null;

        $count = Inquiry::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        // Show only inquiries this user is part of (sent or received). Admin sees all.
        return parent::getEloquentQuery()
            ->when($user && ! $user->isAdmin(), fn ($q) =>
                $q->where(fn ($q2) => $q2
                    ->where('sender_user_id', $user->id)
                    ->orWhere('recipient_user_id', $user->id)
                )
            )
            ->with(['sender', 'recipient', 'artwork.artist']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Placeholder::make('artwork_display')
                    ->label('About artwork')
                    ->content(fn (?Inquiry $record): string => $record?->artwork
                        ? $record->artwork->title.' — '.($record->artwork->artist?->display_name ?? '—')
                        : '—'),
                Forms\Components\Placeholder::make('sender_display')
                    ->label('From')
                    ->content(fn (?Inquiry $record): string => $record?->sender?->name.' <'.$record?->sender?->email.'>' ?? '—'),
                Forms\Components\Placeholder::make('recipient_display')
                    ->label('To')
                    ->content(fn (?Inquiry $record): string => $record?->recipient
                        ? $record->recipient->name.' <'.$record->recipient->email.'>'
                        : '—'),
                Forms\Components\Placeholder::make('sent_at')
                    ->label('Sent')
                    ->content(fn (?Inquiry $record): string => $record?->created_at?->diffForHumans() ?? '—'),
                Forms\Components\Textarea::make('message')
                    ->rows(6)
                    ->columnSpanFull()
                    ->disabled(fn (?Inquiry $record): bool => $record !== null),
                Forms\Components\Select::make('status')
                    ->options([
                        'new'     => 'New',
                        'replied' => 'Replied',
                        'closed'  => 'Closed',
                    ])
                    ->default('new')
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('direction')
                    ->label('')
                    ->state(function (Inquiry $record) use ($user): string {
                        if (! $user) return '';
                        return $record->recipient_user_id === $user->id ? 'IN' : 'OUT';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'IN' ? 'warning' : 'gray')
                    ->icon(fn (string $state): string => $state === 'IN'
                        ? 'heroicon-m-arrow-down-tray'
                        : 'heroicon-m-arrow-up-tray'),
                Tables\Columns\TextColumn::make('artwork.title')
                    ->label('Artwork')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('artwork.artist.last_name')
                    ->label('Artist')
                    ->formatStateUsing(fn (Inquiry $record) => $record->artwork?->artist?->display_name ?? '—'),
                Tables\Columns\TextColumn::make('sender.email')
                    ->label('From')
                    ->limit(28)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recipient.email')
                    ->label('To')
                    ->limit(28)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(fn (Inquiry $r) => $r->message)
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'     => 'warning',
                        'replied' => 'success',
                        'closed'  => 'gray',
                        default   => 'gray',
                    }),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->trueIcon('heroicon-m-eye')
                    ->falseIcon('heroicon-m-eye-slash')
                    ->trueColor('gray')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new'     => 'New',
                        'replied' => 'Replied',
                        'closed'  => 'Closed',
                    ]),
                Tables\Filters\Filter::make('inbox_only')
                    ->label('Inbox (received)')
                    ->query(function ($query) use ($user) {
                        return $user ? $query->where('recipient_user_id', $user->id) : $query;
                    })
                    ->toggle(),
                Tables\Filters\Filter::make('sent_only')
                    ->label('Sent by me')
                    ->query(function ($query) use ($user) {
                        return $user ? $query->where('sender_user_id', $user->id) : $query;
                    })
                    ->toggle(),
                Tables\Filters\Filter::make('unread')
                    ->label('Unread (received only)')
                    ->query(function ($query) use ($user) {
                        return $user
                            ? $query->where('recipient_user_id', $user->id)->whereNull('read_at')
                            : $query;
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->action(fn (Inquiry $record) => $record->markAsRead())
                    ->url(fn (Inquiry $record): string => static::getUrl('edit', ['record' => $record])),
                Tables\Actions\Action::make('mark_replied')
                    ->label('Mark replied')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Inquiry $record): bool => $record->recipient_user_id === auth()->id() && $record->status === 'new')
                    ->action(fn (Inquiry $record) => $record->forceFill([
                        'status'     => 'replied',
                        'replied_at' => now(),
                        'read_at'    => $record->read_at ?? now(),
                    ])->save()),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->visible(fn (Inquiry $record): bool => $record->status !== 'closed')
                    ->requiresConfirmation()
                    ->action(fn (Inquiry $record) => $record->forceFill(['status' => 'closed'])->save()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'edit'  => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
