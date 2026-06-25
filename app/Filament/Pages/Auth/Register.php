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
                    ->helperText('Galleries get full admin. Artists manage their profile + works. Collectors curate private collections. Universities organise exhibitions.'),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data');
    }
}
