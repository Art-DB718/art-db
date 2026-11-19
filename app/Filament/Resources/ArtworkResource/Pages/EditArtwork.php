<?php

namespace App\Filament\Resources\ArtworkResource\Pages;

use App\Filament\Resources\ArtworkResource;
use App\Models\Artwork;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Artwork $record */
        $record = $this->getRecord();

        $slug = Str::slug(($record->artist?->last_name ?? 'artwork').'-'.$record->title);

        // Build a print Action that opens a small modal with a language
        // Select, then dispatches window.open() with ?lang=xx so the PDF
        // renders in that language and loads in a new tab. Every
        // non-condition-report print uses this: the artwork itself has no
        // stored language, so the user picks per click.
        $printWithLanguage = function (
            string $name,
            string $label,
            string $icon,
            string $routeName,
            string $kind,
        ) use ($record, $slug): Actions\Action {
            return Actions\Action::make($name)
                ->label($label)
                ->icon($icon)
                ->modalHeading($label)
                ->modalDescription('Choose the language the PDF should be issued in.')
                ->modalWidth('sm')
                ->modalSubmitActionLabel('Open PDF')
                ->form([
                    Forms\Components\Select::make('lang')
                        ->label('Language')
                        ->options(config('prints.languages'))
                        ->default(config('prints.default_language', 'en'))
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) use ($record, $routeName) {
                    $url = route($routeName, [
                        'artwork' => $record,
                        'lang'    => $data['lang'],
                    ]);
                    // window.open counts as a user gesture because it fires
                    // from a Livewire action the user just submitted — no
                    // popup blocker on current Chrome / Safari / Firefox.
                    $this->js('window.open('.json_encode($url).", '_blank')");
                });
        };

        return [
            Actions\ActionGroup::make([
                $printWithLanguage(
                    'printCard',
                    'Print artwork card',
                    'heroicon-m-printer',
                    'artworks.pdf.card',
                    'card',
                ),
                $printWithLanguage(
                    'printCertificate',
                    'Print certificate',
                    'heroicon-m-shield-check',
                    'artworks.pdf.certificate',
                    'certificate',
                )->visible(! auth()->user()?->isCollector()),
                $printWithLanguage(
                    'printLabel',
                    'Print label',
                    'heroicon-m-tag',
                    'artworks.pdf.label',
                    'label',
                ),
                $printWithLanguage(
                    'printMaintenance',
                    'Print maintenance report',
                    'heroicon-m-wrench-screwdriver',
                    'artworks.pdf.maintenance',
                    'maintenance',
                )->visible(! auth()->user()?->isArtist()),
                // Condition report follows the language the user picked
                // at the top of the Condition Report tab (stored under
                // condition_report.language on the model). No modal — the
                // choice already lives on the record.
                Actions\Action::make('printConditionReport')
                    ->label('Print condition report')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->visible(! auth()->user()?->isArtist())
                    ->url(function () use ($record) {
                        $lang = data_get($record->condition_report, 'language')
                            ?: config('prints.default_language', 'en');
                        return route('artworks.pdf.condition-report', [
                            'artwork' => $record,
                            'lang'    => $lang,
                        ]);
                    })
                    ->openUrlInNewTab()
                    ->extraAttributes(function () use ($record, $slug) {
                        $lang = data_get($record->condition_report, 'language')
                            ?: config('prints.default_language', 'en');
                        return [
                            'data-pdf-share' => route('artworks.pdf.condition-report', [
                                'artwork' => $record,
                                'lang'    => $lang,
                            ]),
                            'data-pdf-filename' => 'condition-report-'.$lang.'-'.$slug.'.pdf',
                        ];
                    }),
            ])
                ->label('Print / Save as PDF')
                ->icon('heroicon-m-printer')
                ->button(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
