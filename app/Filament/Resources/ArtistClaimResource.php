<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistClaimResource\Pages;
use App\Mail\ArtistClaimApproved;
use App\Mail\ArtistClaimRejected;
use App\Models\ArtistClaim;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class ArtistClaimResource extends Resource
{
    protected static ?string $model = ArtistClaim::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Catalogue';
    protected static ?string $navigationLabel = 'Artist claims';
    protected static ?int $navigationSort = 15;

    /**
     * Only owners of the claimed Artist (typically Gallery users) — and admins —
     * see this resource. Artist users don't need it: their own claim status
     * shows up in their notifications only.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) return false;
        if ($user->isAdmin()) return true;
        return static::getEloquentQuery()->exists();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user && ! $user->isAdmin()) {
            // Only claims for artists this user owns.
            $q->whereHas('artist', fn ($qq) => $qq->where('owner_user_id', $user->id));
        }
        return $q;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('artist.display_name')
                    ->label('Artist')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('claimant.name')
                    ->label('Claimant')
                    ->description(fn (ArtistClaim $r) => $r->claimant?->email)
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => ArtistClaim::STATUS_PENDING,
                        'success' => ArtistClaim::STATUS_APPROVED,
                        'danger'  => ArtistClaim::STATUS_REJECTED,
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Responded')
                    ->since()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ArtistClaim::STATUS_PENDING  => 'Pending',
                        ArtistClaim::STATUS_APPROVED => 'Approved',
                        ArtistClaim::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn (ArtistClaim $r) => $r->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Approve ownership transfer')
                    ->modalDescription(fn (ArtistClaim $r) =>
                        "Transfer '{$r->artist->display_name}' to {$r->claimant->name} ({$r->claimant->email})? "
                      . 'Your gallery will keep them in the represented-artists roster.')
                    ->action(function (ArtistClaim $r) {
                        $r->approve(auth()->user());
                        try {
                            Mail::to($r->claimant->email)->send(new ArtistClaimApproved($r));
                        } catch (\Throwable $e) {
                            logger()->warning('Approve mail failed: '.$e->getMessage());
                        }
                        Notification::make()->title('Claim approved')->success()->send();
                    }),

                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-m-x-circle')
                    ->visible(fn (ArtistClaim $r) => $r->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Reject ownership transfer')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Reason (optional, shown to claimant in email)')
                            ->rows(3),
                    ])
                    ->action(function (ArtistClaim $r, array $data) {
                        $r->reject(auth()->user(), $data['reason'] ?? null);
                        try {
                            Mail::to($r->claimant->email)->send(new ArtistClaimRejected($r));
                        } catch (\Throwable $e) {
                            logger()->warning('Reject mail failed: '.$e->getMessage());
                        }
                        Notification::make()->title('Claim rejected')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtistClaims::route('/'),
        ];
    }
}
