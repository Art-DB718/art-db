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
     * After registration every user — Gallery, Artist, Collector alike —
     * starts on a 14-day full-feature trial. When the trial ends without
     * a paid subscription the account flips to past_due (read-only) via
     * the EnforceSubscriptionStatus middleware + subscriptions:check job.
     */
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = parent::handleRegistration($data);

        $user->forceFill([
            'subscription_plan'   => null,
            'subscription_status' => 'trial',
            'trial_ends_at'       => now()->addDays((int) config('subscription.trial_days', 14)),
        ])->save();

        return $user;
    }
}
