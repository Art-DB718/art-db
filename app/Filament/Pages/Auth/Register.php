<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Mail\ArtistClaimRequested;
use App\Models\Artist;
use App\Models\ArtistClaim;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Mail;

class Register extends BaseRegister
{
    /**
     * Rebuild the email field from scratch (not extending parent) so we can
     * install a single unique rule scoped to non-deleted rows. Extending
     * parent would layer both rules — the base's ->unique() ignores soft
     * deletes and re-triggers on the leftover row.
     *
     * The DB-side partial index (users_email_unique WHERE deleted_at IS NULL)
     * enforces the same shape at insert time.
     */
    protected function getEmailFormComponent(): Component
    {
        return \Filament\Forms\Components\TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(
                table: 'users',
                column: 'email',
                modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'),
            );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Select::make('role')
                    ->label('I am registering as')
                    ->options(collect(UserRole::publicRegisterChoices())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()])->all())
                    ->required()
                    ->native(false)
                    ->helperText('Galleries get full admin. Artists manage their profile + works. Collectors curate private collections.'),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Per-role registration defaults:
     *   - Artist  → 'artist_free' plan, status 'active' (forever free, 20 works)
     *   - Gallery + Collector → 14-day full-feature trial → past_due on expiry
     *
     * Artist auto-match: if a User registers as Artist and there's already an
     * Artist row with a matching first_name + last_name owned by someone else
     * (typically a gallery who added them), we open an ArtistClaim (pending)
     * and email the current owner. Approval transfers ownership and keeps the
     * gallery pivot link so nothing disappears publicly.
     */
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = parent::handleRegistration($data);

        $isArtist = ($data['role'] ?? null) === 'artist';
        $user->forceFill([
            'subscription_plan'   => $isArtist ? 'artist_free' : null,
            'subscription_status' => $isArtist ? 'active' : 'trial',
            'trial_ends_at'       => $isArtist ? null : now()->addDays((int) config('subscription.trial_days', 14)),
        ])->save();

        if ($isArtist) {
            $this->handleArtistProfileMatch($user, $data['name'] ?? '');
        }

        return $user;
    }

    /**
     * Auto-match a newly-registered Artist user to an existing Artist row.
     *   - Split display name into first/last (last token = last_name, rest = first_name)
     *   - Look for a case-insensitive match on both parts
     *   - If found + owned by someone else → open pending claim + notify owner
     *   - If found + already ownerless (owner_user_id is null) → adopt it directly
     *   - Otherwise → do nothing (Artist user creates their own record later)
     */
    protected function handleArtistProfileMatch(\App\Models\User $user, string $fullName): void
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if (count($parts) < 2) {
            return;
        }
        $lastName  = array_pop($parts);
        $firstName = implode(' ', $parts);

        $existing = Artist::query()
            ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($firstName)])
            ->whereRaw('LOWER(last_name) = ?',  [mb_strtolower($lastName)])
            ->first();

        if (! $existing) {
            return;
        }

        // Ownerless (imported / seeded) → adopt directly, no claim needed.
        if (is_null($existing->owner_user_id)) {
            $existing->forceFill(['owner_user_id' => $user->id])->save();
            Notification::make()
                ->title('Existing profile linked')
                ->body("We found an unowned profile for {$firstName} {$lastName} and linked it to your account.")
                ->success()
                ->send();
            return;
        }

        // Already owned by this same user (shouldn't really happen right after
        // registration, but be idempotent).
        if ($existing->owner_user_id === $user->id) {
            return;
        }

        // Owned by someone else — open a pending claim.
        $claim = ArtistClaim::create([
            'artist_id'        => $existing->id,
            'claimant_user_id' => $user->id,
            'status'           => ArtistClaim::STATUS_PENDING,
        ]);

        $owner = $existing->owner;
        if ($owner?->email) {
            try {
                Mail::to($owner->email)->send(new ArtistClaimRequested($claim));
            } catch (\Throwable $e) {
                logger()->warning('Artist claim mail failed for '.$owner->email.': '.$e->getMessage());
            }
        }

        Notification::make()
            ->title('Existing profile found')
            ->body("A profile for {$firstName} {$lastName} already exists in the database. "
                 . 'The current owner has been notified — you\'ll receive an email once they respond.')
            ->warning()
            ->persistent()
            ->send();
    }
}
