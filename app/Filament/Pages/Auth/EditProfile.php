<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Extended profile page: adds a 'Delete my account' danger-zone action
 * next to the default Save / Cancel actions in the header, plus a Push
 * notifications toggle rendered via a Blade view below the password block.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                Section::make('Notifikácie')
                    ->description('Push upozornenia na nové správy a predaje priamo do telefónu.')
                    ->collapsible()
                    ->schema([
                        View::make('filament.forms.push-notifications')
                            ->viewData([
                                'subscriptionCount' => Auth::user()?->pushSubscriptions()->count() ?? 0,
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Appends our destructive action to the built-in Save / Cancel row.
     * Filament's base page renders these under the form; our button sits
     * after Save/Cancel so it's clearly separated from the primary action.
     */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('deleteAccount')
                ->label('Delete my account')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Delete your account?')
                ->modalDescription('This action deactivates your account immediately. Your public artist / gallery profile and any works you own stay in the database (so links keep working) but are no longer editable. Contact support within 30 days if you want it fully restored — after that, everything gets permanently purged.')
                ->modalSubmitActionLabel('Yes, delete my account')
                ->form([
                    TextInput::make('current_password')
                        ->label('Enter your current password to confirm')
                        ->password()
                        ->required()
                        ->autocomplete('current-password'),
                ])
                ->action(function (array $data) {
                    $user = Auth::user();

                    if (! Hash::check($data['current_password'], $user->password)) {
                        Notification::make()
                            ->title('Wrong password')
                            ->body('We could not verify your password. Account not deleted.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Soft-delete the user + kill the session immediately so
                    // no lingering request can revive them.
                    $user->delete();
                    Auth::guard('web')->logout();
                    session()->invalidate();
                    session()->regenerateToken();

                    $this->redirect('/', navigate: false);
                }),
        ];
    }
}
