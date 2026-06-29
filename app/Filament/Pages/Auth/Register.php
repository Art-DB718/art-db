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
     * After registration: Collectors get the free 'collector_free' plan
     * indefinitely. Everyone else starts on a 14-day trial that converts
     * to past_due (read-only) when it expires.
     */
    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = parent::handleRegistration($data);

        $isCollector = ($data['role'] ?? null) === 'collector';
        $user->forceFill([
            'subscription_plan'   => $isCollector ? 'collector_free' : null,
            'subscription_status' => $isCollector ? 'active' : 'trial',
            'trial_ends_at'       => $isCollector ? null : now()->addDays((int) config('subscription.trial_days', 14)),
        ])->save();

        return $user;
    }
}
