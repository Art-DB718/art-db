<?php

namespace App\Filament\Pages;

use App\Models\InvoiceSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class InvoiceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Invoice design';
    protected static ?string $title = 'Invoice design';
    protected static string $view = 'filament.pages.invoice-settings';

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u && ($u->isAdmin() || $u->isGallery());
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(InvoiceSetting::current()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branding')
                    ->description('Logo zobrazené v hlavičke faktúry.')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('invoice'),
                    ]),

                Forms\Components\Section::make('Company')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')->maxLength(255)->columnSpanFull(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('business_id')->label('IČO')->maxLength(50),
                            Forms\Components\TextInput::make('tax_id')->label('DIČ')->maxLength(50),
                            Forms\Components\TextInput::make('vat_id')->label('IČ DPH')->maxLength(50),
                        ]),
                    ]),

                Forms\Components\Section::make('Address')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('address_line1')->label('Address line 1')->maxLength(255)->columnSpanFull(),
                        Forms\Components\TextInput::make('address_line2')->label('Address line 2')->maxLength(255)->columnSpanFull(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('city')->maxLength(255),
                            Forms\Components\TextInput::make('postal_code')->maxLength(20),
                            Forms\Components\TextInput::make('country')->maxLength(255),
                        ]),
                    ]),

                Forms\Components\Section::make('Contact')
                    ->icon('heroicon-o-at-symbol')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('email')->email()->maxLength(255),
                            Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
                            Forms\Components\TextInput::make('website')->maxLength(255)
                                ->placeholder('https://example.com'),
                        ]),
                    ]),

                Forms\Components\Section::make('Banking')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('bank_name')->maxLength(255),
                            Forms\Components\TextInput::make('bank_account')->label('IBAN / Account number')->maxLength(64),
                        ]),
                    ]),

                Forms\Components\Section::make('Footer notes')
                    ->description('Vlastný text v päte faktúry (napr. zákonné poučenia, ďakovná veta).')
                    ->schema([
                        Forms\Components\Textarea::make('footer_notes')->rows(3)->columnSpanFull(),
                    ])->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        InvoiceSetting::current()->update($data);

        Notification::make()
            ->title('Invoice design saved')
            ->success()
            ->send();
    }
}
