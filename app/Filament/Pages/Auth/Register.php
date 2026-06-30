<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
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

        return $user;
    }
}
