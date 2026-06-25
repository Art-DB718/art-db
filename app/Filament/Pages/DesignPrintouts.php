<?php

namespace App\Filament\Pages;

use App\Models\Artwork;
use App\Models\InvoiceSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class DesignPrintouts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Design printouts';
    protected static ?string $title = 'Design printouts';
    protected static string $view = 'filament.pages.design-printouts';

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
                Forms\Components\Tabs::make('Printouts')
                    ->tabs([

                        Forms\Components\Tabs\Tab::make('Certificate of Authenticity')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\RichEditor::make('cert_intro')
                                    ->label('Intro paragraph')
                                    ->live(debounce: 600)
                                    ->formatStateUsing(fn (?string $state): string => filled($state)
                                        ? $state
                                        : '<p>This certificate confirms that the artwork described below is an original work by the named artist and was acquired from our gallery.</p>')
                                    ->helperText('Shown between the title and the artwork details.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('cert_signature_label')
                                    ->label('Signature line label')
                                    ->live(debounce: 600)
                                    ->placeholder('Issued by — Schottert Contemporary')
                                    ->helperText('Caption shown under the signature line on the left.'),

                                $this->previewPlaceholder('certificate'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Artwork Card')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\ToggleButtons::make('card_size')
                                    ->label('Paper size')
                                    ->live()
                                    ->options([
                                        'a4'     => 'A4 (210 × 297 mm)',
                                        'a5'     => 'A5 (148 × 210 mm)',
                                        'letter' => 'Letter (8.5 × 11 in)',
                                    ])
                                    ->icons([
                                        'a4'     => 'heroicon-m-document',
                                        'a5'     => 'heroicon-m-document-minus',
                                        'letter' => 'heroicon-m-document',
                                    ])
                                    ->default('a4')
                                    ->inline()
                                    ->helperText('Page size of the printable artwork card.'),
                                Forms\Components\TextInput::make('card_footer_text')
                                    ->label('Footer text')
                                    ->live(debounce: 600)
                                    ->placeholder('Inquiries: art@schottert-contemporary.com')
                                    ->helperText('Custom text in the footer. If empty, the company name + email from Invoice design is used.')
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('card_show_price')
                                    ->label('Show price')
                                    ->live()
                                    ->default(true),
                                Forms\Components\Toggle::make('card_show_gallery')
                                    ->label('Show gallery images')
                                    ->live()
                                    ->helperText('Renders a grid of the artwork’s additional gallery images at the bottom of the card.')
                                    ->default(false),
                                Forms\Components\Toggle::make('card_show_provenance')
                                    ->label('Show provenance section (when filled on the artwork)')
                                    ->live()
                                    ->default(false),

                                $this->previewPlaceholder('card'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Artwork Label')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\ToggleButtons::make('label_size')
                                    ->label('Label size')
                                    ->live()
                                    ->options([
                                        'small'    => 'Small (60 × 40 mm)',
                                        'standard' => 'Standard (85 × 55 mm)',
                                        'large'    => 'Large (105 × 70 mm)',
                                        'a6'       => 'A6 (148 × 105 mm)',
                                    ])
                                    ->default('standard')
                                    ->inline()
                                    ->helperText('Physical size of the printed label. The browser prints to this paper size automatically.'),
                                Forms\Components\Toggle::make('label_show_logo')
                                    ->label('Show gallery logo')
                                    ->live()
                                    ->default(false)
                                    ->helperText('Uses the logo uploaded in Invoice design.'),
                                Forms\Components\Toggle::make('label_show_price')
                                    ->label('Show price')
                                    ->live()
                                    ->default(true),
                                Forms\Components\Toggle::make('label_show_dimensions')
                                    ->label('Show dimensions')
                                    ->live()
                                    ->default(true),

                                $this->previewPlaceholder('label'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Maintenance Report')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\Placeholder::make('maintenance_intro')
                                    ->label('')
                                    ->content(new HtmlString('<p style="color:#6b7280;font-size:0.9rem;">The maintenance report has no configurable design fields — it uses the company name &amp; footer from <em>Invoice design</em>. Below is a live preview generated from the first artwork that has any maintenance records.</p>')),

                                $this->previewPlaceholder('maintenance'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * Build a Filament Placeholder that renders a printout view inside an iframe.
     * Picks a sample artwork (with primary_image preferred), feeds the current
     * form state in as a transient InvoiceSetting, renders the *-pdf blade.
     */
    protected function previewPlaceholder(string $kind): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('preview_'.$kind)
            ->label('Live preview')
            ->columnSpanFull()
            ->content(function () use ($kind): HtmlString {
                $artwork = $this->sampleArtwork($kind);

                if (! $artwork) {
                    return new HtmlString('<div style="padding:1rem;background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;color:#78350f;">No artworks in the database yet — preview unavailable. Add an artwork first.</div>');
                }

                // Merge persisted settings with current form state so the preview
                // reflects unsaved changes immediately.
                $persisted = InvoiceSetting::current()->toArray();
                $formState = $this->data ?? [];
                $settings  = new InvoiceSetting(array_merge($persisted, $formState));

                $viewName = match ($kind) {
                    'card'        => 'prints.artwork-card-pdf',
                    'certificate' => 'prints.artwork-certificate-pdf',
                    'label'       => 'prints.artwork-label-pdf',
                    'maintenance' => 'prints.artwork-maintenance-pdf',
                };

                try {
                    $html = view($viewName, ['artwork' => $artwork, 'settings' => $settings])->render();
                } catch (\Throwable $e) {
                    return new HtmlString('<div style="padding:1rem;background:#fee2e2;border:1px solid #ef4444;border-radius:6px;color:#7f1d1d;">Preview render failed: '.e($e->getMessage()).'</div>');
                }

                // Inject <base> so storage URLs resolve correctly inside the iframe sandbox.
                $base = '<base href="'.url('/').'/">';
                $html = str_replace('<head>', '<head>'.$base, $html);

                $height = match ($kind) {
                    'label' => 360,
                    default => 760,
                };

                return new HtmlString(sprintf(
                    '<iframe sandbox="allow-same-origin" srcdoc="%s" style="width:100%%;height:%dpx;border:1px solid #d1d5db;border-radius:6px;background:#fff;display:block;"></iframe>'
                    .'<div style="font-size:0.78rem;color:#6b7280;margin-top:0.4rem;">Sample artwork: <strong>%s</strong>%s — preview updates when you change fields.</div>',
                    e($html),
                    $height,
                    e($artwork->title),
                    $artwork->artist ? ' by '.e($artwork->artist->display_name) : ''
                ));
            });
    }

    /**
     * Pick a sample artwork for the preview. For maintenance, prefer one with
     * maintenance records; for everything else, prefer one with a primary_image.
     */
    protected function sampleArtwork(string $kind): ?Artwork
    {
        $query = Artwork::query()->with(['artist', 'medium']);

        if ($kind === 'maintenance') {
            $found = (clone $query)->has('maintenances')->with('maintenances')->first();
            if ($found) return $found;
        }

        return (clone $query)->whereNotNull('primary_image')->first()
            ?? $query->first();
    }

    public function save(): void
    {
        InvoiceSetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Printout design saved')
            ->success()
            ->send();
    }
}
