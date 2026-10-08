<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivateRoomResource\Pages;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\PrivateRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class PrivateRoomResource extends Resource
{
    protected static ?string $model = PrivateRoom::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Commerce';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'title';

    public const SORT_STRATEGIES = [
        'manual'     => 'Manual order',
        'price_asc'  => 'Price — low to high',
        'price_desc' => 'Price — high to low',
        'year_desc'  => 'Year — newest first',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([

                Forms\Components\Wizard\Step::make('Overview')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()->maxLength(255)->columnSpanFull(),
                        Forms\Components\Textarea::make('welcome_message')
                            ->rows(4)->columnSpanFull()
                            ->helperText('Shown at the top of the private room AND used as the main body of the email sent to recipients.'),
                        Forms\Components\FileUpload::make('cover_image')
                            ->image()->disk('public')->directory('private-rooms'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->helperText('Leave empty for no expiry.'),
                    ]),

                Forms\Components\Wizard\Step::make('Choose Artworks')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        // Narrowing filters — each one restricts the artwork
                        // picker below. All optional; combine as needed.
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('filter_artist_id')
                                ->label('Filter by artist')
                                ->dehydrated(false)
                                ->options(fn () => \App\Models\Artist::query()
                                    ->orderBy('last_name')->orderBy('first_name')
                                    ->get()->mapWithKeys(fn ($a) => [$a->id => trim(($a->first_name ?? '').' '.($a->last_name ?? ''))])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->live(),
                            Forms\Components\Select::make('filter_collection_id')
                                ->label('Filter by collection')
                                ->dehydrated(false)
                                ->options(fn () => \App\Models\Collection::query()
                                    ->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->live(),
                            Forms\Components\Select::make('filter_medium_id')
                                ->label('Filter by technique')
                                ->dehydrated(false)
                                ->options(fn () => \App\Models\Medium::query()
                                    ->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->live(),
                        ]),

                        Forms\Components\Select::make('artworks')
                            ->relationship(
                                name: 'artworks',
                                titleAttribute: 'title',
                                // Apply the three filters + join artist so the
                                // "search by title" also matches artist names.
                                modifyQueryUsing: function ($query, Forms\Get $get) {
                                    if ($artistId = $get('filter_artist_id')) {
                                        $query->where('artist_id', $artistId);
                                    }
                                    if ($collectionId = $get('filter_collection_id')) {
                                        $query->whereHas('collections', fn ($q) => $q->where('collections.id', $collectionId));
                                    }
                                    if ($mediumId = $get('filter_medium_id')) {
                                        $query->where('medium_id', $mediumId);
                                    }
                                    return $query->with(['artist', 'medium']);
                                },
                            )
                            ->multiple()
                            // Search matches title, inventory ID and artist name.
                            ->getSearchResultsUsing(function (string $search) {
                                return Artwork::query()
                                    ->with('artist')
                                    ->where(function ($q) use ($search) {
                                        $like = '%'.$search.'%';
                                        $q->where('title', 'ilike', $like)
                                          ->orWhere('inventory_id', 'ilike', $like)
                                          ->orWhereHas('artist', function ($sub) use ($like) {
                                              $sub->where('first_name', 'ilike', $like)
                                                  ->orWhere('last_name', 'ilike', $like);
                                          });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Artwork $r) => [$r->id => self::artworkOptionLabel($r)])
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): string => ($a = Artwork::with('artist')->find($value)) ? self::artworkOptionLabel($a) : '—')
                            ->preload()
                            ->allowHtml()
                            ->getOptionLabelFromRecordUsing(fn (Artwork $record): string => self::artworkOptionLabel($record))
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) => self::syncManualLineup($state, $get, $set))
                            ->columnSpanFull()
                            ->helperText('Narrow the list with the filters above, or type to search by title, inventory ID or artist name.'),
                    ]),

                Forms\Components\Wizard\Step::make('Pricing & Sort')
                    ->icon('heroicon-o-currency-euro')
                    ->schema([
                        Forms\Components\Toggle::make('show_prices')
                            ->label('Show prices to the client')
                            ->default(true),
                        Forms\Components\Select::make('sort_strategy')
                            ->options(self::SORT_STRATEGIES)
                            ->default('manual')
                            ->required()
                            ->live(),
                        Forms\Components\ToggleButtons::make('discount_percent')
                            ->label('Discount applied to all prices')
                            ->options([
                                0  => 'None',
                                10 => '-10 %',
                                15 => '-15 %',
                                20 => '-20 %',
                                25 => '-25 %',
                            ])
                            ->colors([
                                0  => 'gray',
                                10 => 'warning',
                                15 => 'warning',
                                20 => 'danger',
                                25 => 'danger',
                            ])
                            ->default(0)
                            ->inline()
                            ->live()
                            ->helperText('Reduces every artwork price shown to the client by this percentage.'),
                        // Manuálne usporiadanie — Repeater s drag-and-drop
                        Forms\Components\Repeater::make('manual_lineup')
                            ->label('Manual order — drag rows to reorder')
                            ->dehydrated(false)
                            ->visible(fn (Forms\Get $get): bool => ($get('sort_strategy') ?? 'manual') === 'manual')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->collapsible(false)
                            ->addable(false)
                            ->deletable(false)
                            ->itemLabel(function (array $state): ?string {
                                $a = isset($state['artwork_id'])
                                    ? Artwork::with('artist')->find($state['artwork_id'])
                                    : null;

                                return $a ? trim(($a->artist?->display_name ?? '').' — '.$a->title) : '—';
                            })
                            ->schema([
                                Forms\Components\Hidden::make('artwork_id'),
                                Forms\Components\Placeholder::make('artwork_preview')
                                    ->hiddenLabel()
                                    ->content(fn (Forms\Get $get): HtmlString => self::renderSingleArtworkCard(
                                        (int) ($get('artwork_id') ?? 0),
                                        (int) ($get('../../discount_percent') ?? 0),
                                    )),
                            ])
                            ->columnSpanFull(),

                        // Náhľad pre nepresne usporiadané stratégie (price/year)
                        Forms\Components\Placeholder::make('artwork_lineup')
                            ->label(fn (Forms\Get $get): string => match ($get('sort_strategy') ?? 'manual') {
                                'price_asc'  => 'Preview — sorted by price (low → high)',
                                'price_desc' => 'Preview — sorted by price (high → low)',
                                'year_desc'  => 'Preview — sorted by year (newest first)',
                                default      => '',
                            })
                            ->visible(fn (Forms\Get $get): bool => ($get('sort_strategy') ?? 'manual') !== 'manual')
                            ->content(fn (Forms\Get $get): HtmlString => self::renderArtworkPreview(
                                $get('artworks') ?? [],
                                $get('sort_strategy') ?? 'manual',
                                (int) ($get('discount_percent') ?? 0),
                            ))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Wizard\Step::make('Select Contacts')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Select::make('contact_groups')
                            ->label('Add contacts by group')
                            ->options(fn (): array => ContactGroup::query()
                                ->orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set): void {
                                $groupIds = collect((array) $state)->map(fn ($v) => (int) $v)->all();
                                if (empty($groupIds)) {
                                    return;
                                }
                                // Membership lives on the M:N pivot
                                // (contact_contact_group). The old 1-N
                                // group_id was left in place for legacy
                                // reads but new group assignments — every
                                // artgalleria-imported member — live only
                                // on the pivot, so we query through the
                                // relation.
                                $contactIds = Contact::query()
                                    ->whereHas('groups', fn ($q) => $q->whereIn('contact_groups.id', $groupIds))
                                    ->pluck('id')
                                    ->all();
                                $merged = collect($get('recipients') ?? [])
                                    ->map(fn ($v) => (int) $v)
                                    ->merge($contactIds)
                                    ->unique()
                                    ->values()
                                    ->all();
                                $set('recipients', $merged);
                            })
                            ->helperText('Selecting a group adds all its contacts to the recipients below. Already-picked contacts are kept.')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('recipients')
                            ->label('Recipients (from contacts)')
                            ->relationship('recipients', 'last_name')
                            ->multiple()
                            ->searchable(['first_name', 'last_name', 'organization', 'email'])
                            ->preload()
                            ->allowHtml()
                            ->getOptionLabelFromRecordUsing(fn (Contact $record): string => self::contactRecipientLabel($record))
                            ->columnSpanFull()
                            ->helperText('Pick individual contacts, or use the group picker above to add several at once.'),
                        Forms\Components\Toggle::make('allow_inquiry')
                            ->label('Allow the client to send an inquiry')
                            ->default(true),
                    ]),

                Forms\Components\Wizard\Step::make('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->schema([
                        Forms\Components\Placeholder::make('public_url')
                            ->label('Public link')
                            ->content(fn (?PrivateRoom $record) => $record?->publicUrl()
                                ?? 'The shareable link is generated once you save the room.'),
                        Forms\Components\Placeholder::make('recipients_info')
                            ->label('Recipients')
                            ->content(fn (?PrivateRoom $record): string => $record
                                ? $record->recipients()->count().' total · '
                                    .$record->recipients()->wherePivot('status', 'sent')->count().' already sent'
                                : 'Save the room first to manage and send to recipients.'),
                        Forms\Components\Placeholder::make('sent_info')
                            ->label('First sent at')
                            ->content(fn (?PrivateRoom $record) => $record?->sent_at?->format('d.m.Y H:i')
                                ?? 'Not sent yet.'),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('sendToRecipients')
                                ->label('Send to recipients')
                                ->icon('heroicon-m-paper-airplane')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalDescription('Emails the private room link to every recipient with an email address who has not received it yet.')
                                ->visible(fn (?PrivateRoom $record): bool => $record !== null)
                                ->action(function (PrivateRoom $record, $livewire): void {
                                    self::sendToRecipients($record);
                                    $livewire->redirect(self::getUrl('index'));
                                }),
                        ]),
                    ]),

            ])->columnSpanFull()->skippable(),
        ]);
    }

    /**
     * HTML menovka diela pre Select — náhľad fotky + autor, názov a inventárne číslo
     * (rovnaký formát ako v CollectionResource).
     */
    protected static function artworkOptionLabel(Artwork $record): string
    {
        $image = $record->primary_image
            ? '<img src="'.e(Storage::url($record->primary_image)).'" alt="" '
                .'style="width:2.75rem;height:2.75rem;border-radius:0.375rem;object-fit:cover;flex:none;">'
            : '<span style="width:2.75rem;height:2.75rem;border-radius:0.375rem;flex:none;'
                .'background:rgba(120,120,130,0.25);display:flex;align-items:center;'
                .'justify-content:center;font-size:0.85rem;opacity:0.6;">&#9711;</span>';

        $title  = e($record->title);
        $artist = e($record->artist?->display_name ?? 'Unknown artist');
        $inv    = e($record->inventory_id);

        return '<div style="display:flex;align-items:center;gap:0.625rem;">'
            .$image
            .'<span style="line-height:1.25;">'
            .'<span style="font-weight:600;">'.$title.'</span><br>'
            .'<span style="font-size:0.75rem;opacity:0.7;">'.$artist.' &middot; '.$inv.'</span>'
            .'</span></div>';
    }

    /**
     * Sync helper — pri zmene výberu diel udržiava manual_lineup (Repeater) v súlade:
     * zachová existujúce poradie pre už zaradené diela a nové prilepí na koniec.
     */
    protected static function syncManualLineup($selectedState, Forms\Get $get, Forms\Set $set): void
    {
        $selected = collect((array) $selectedState)->map(fn ($v) => (int) $v);

        $current = collect($get('manual_lineup') ?? [])
            ->map(fn ($item) => (int) ($item['artwork_id'] ?? 0))
            ->filter();

        $kept    = $current->filter(fn ($id) => $selected->contains($id));
        $toAdd   = $selected->diff($kept);
        $merged  = $kept->concat($toAdd);

        $set('manual_lineup', $merged->map(fn ($id) => ['artwork_id' => $id])->values()->all());
    }

    /**
     * Vykreslí jedno dielo ako kompaktnú kartu (náhľad + autor/názov/cena s prípadnou zľavou).
     * Používa sa vnútri Repeater itemu pri manuálnom usporiadaní.
     */
    protected static function renderSingleArtworkCard(int $artworkId, int $discountPercent = 0): HtmlString
    {
        if (! $artworkId) {
            return new HtmlString('<div style="color:#9ca3af;font-style:italic;">No artwork.</div>');
        }
        $art = Artwork::with('artist')->find($artworkId);
        if (! $art) {
            return new HtmlString('<div style="color:#9ca3af;font-style:italic;">Artwork not found.</div>');
        }

        $thumb = $art->primary_image
            ? '<img src="'.e(Storage::url($art->primary_image)).'" alt="" '
                .'style="width:64px;height:64px;border-radius:0.4rem;object-fit:cover;flex:none;">'
            : '<div style="width:64px;height:64px;border-radius:0.4rem;background:rgba(120,120,130,0.2);'
                .'display:flex;align-items:center;justify-content:center;color:#9ca3af;flex:none;font-size:1.2rem;">&#9711;</div>';

        $artist = e($art->artist?->display_name ?? '—');
        $title  = e($art->title);

        $original = (float) ($art->price ?? 0);
        $currency = e($art->currency ?? '');
        $priceHtml = '';
        if ($original > 0) {
            if ($discountPercent > 0) {
                $discounted = round($original * (1 - $discountPercent / 100), 2);
                $priceHtml = '<span style="text-decoration:line-through;opacity:0.45;margin-right:0.35rem;">'
                        .number_format($original, 0, '.', ' ').' '.$currency.'</span>'
                    .'<strong style="color:#10b981;">'
                        .number_format($discounted, 0, '.', ' ').' '.$currency.'</strong>';
            } else {
                $priceHtml = number_format($original, 0, '.', ' ').' '.$currency;
            }
        }

        $year = $art->year_created ? e((string) $art->year_created) : '';
        $metaBits = array_filter([$year, $priceHtml]);
        $meta = implode(' · ', $metaBits);

        return new HtmlString(
            '<div style="display:flex;align-items:center;gap:0.75rem;">'
            .$thumb
            .'<div style="line-height:1.3;">'
            .'<div style="font-weight:600;">'.$artist.'</div>'
            .'<div style="opacity:0.75;">'.$title.'</div>'
            .($meta !== '' ? '<div style="opacity:0.7;font-size:0.8rem;margin-top:0.15rem;">'.$meta.'</div>' : '')
            .'</div></div>'
        );
    }

    /**
     * Vykreslí mriežku diel zoradenú podľa zvolenej stratégie a aplikuje zľavu (% z ceny).
     * Pre 'manual' rešpektuje poradie z $artworkIds (= poradie výberu v kroku „Choose Artworks").
     */
    protected static function renderArtworkPreview(array $artworkIds, string $strategy, int $discountPercent = 0): HtmlString
    {
        $artworkIds = collect($artworkIds)->map(fn ($v) => (int) $v)->filter()->values();

        if ($artworkIds->isEmpty()) {
            return new HtmlString('<div style="color:#9ca3af;font-style:italic;padding:0.5rem 0;">'
                .'No artworks selected yet — pick them in step 2.</div>');
        }

        $artworks = Artwork::with('artist')->whereIn('id', $artworkIds->all())->get()->keyBy('id');

        $sorted = match ($strategy) {
            'price_asc'  => $artworks->sortBy(fn ($a) => (float) ($a->price ?? PHP_INT_MAX))->values(),
            'price_desc' => $artworks->sortByDesc(fn ($a) => (float) ($a->price ?? 0))->values(),
            'year_desc'  => $artworks->sortByDesc(fn ($a) => (int) ($a->year_created ?? 0))->values(),
            default      => $artworkIds->map(fn ($id) => $artworks->get($id))->filter()->values(),
        };

        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:0.75rem;margin-top:0.25rem;">';
        foreach ($sorted as $i => $art) {
            $thumb = $art->primary_image
                ? '<img src="'.e(Storage::url($art->primary_image)).'" alt="" '
                    .'style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:0.4rem;display:block;">'
                : '<div style="width:100%;aspect-ratio:1;background:rgba(120,120,130,0.2);border-radius:0.4rem;'
                    .'display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:1.3rem;">&#9711;</div>';

            $artist = e($art->artist?->display_name ?? '—');
            $title  = e($art->title);

            // Cena s prípadnou zľavou
            $original = (float) ($art->price ?? 0);
            $currency = e($art->currency ?? '');
            $priceHtml = '';
            if ($original > 0) {
                if ($discountPercent > 0) {
                    $discounted = round($original * (1 - $discountPercent / 100), 2);
                    $priceHtml = '<span style="text-decoration:line-through;opacity:0.45;margin-right:0.35rem;">'
                            .number_format($original, 0, '.', ' ').' '.$currency.'</span>'
                        .'<strong style="color:#10b981;">'
                            .number_format($discounted, 0, '.', ' ').' '.$currency.'</strong>';
                } else {
                    $priceHtml = number_format($original, 0, '.', ' ').' '.$currency;
                }
            }

            $metaBits = [];
            if ($art->year_created) {
                $metaBits[] = e((string) $art->year_created);
            }
            if ($priceHtml !== '') {
                $metaBits[] = $priceHtml;
            }
            $metaHtml = implode(' · ', $metaBits);

            $html .= '<div style="font-size:0.85rem;">'
                .'<div style="position:relative;">'.$thumb
                .'<span style="position:absolute;top:0.35rem;left:0.35rem;background:rgba(0,0,0,0.7);color:#fff;'
                    .'padding:0.1rem 0.45rem;border-radius:0.25rem;font-size:0.7rem;font-weight:700;">'.($i + 1).'</span>'
                .'</div>'
                .'<div style="margin-top:0.4rem;font-weight:600;line-height:1.25;">'.$artist.'</div>'
                .'<div style="opacity:0.75;line-height:1.25;">'.$title.'</div>'
                .($metaHtml !== '' ? '<div style="opacity:0.7;font-size:0.75rem;margin-top:0.15rem;">'.$metaHtml.'</div>' : '')
                .'</div>';
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    /** HTML menovka kontaktu pre multi-select príjemcov — meno + organizácia/email. */
    protected static function contactRecipientLabel(Contact $record): string
    {
        $name = e($record->display_name);
        $meta = collect([$record->organization, $record->email])
            ->filter()
            ->map(fn ($v) => e($v))
            ->implode(' · ');

        return '<span style="line-height:1.25;display:inline-block;">'
            .'<span style="font-weight:600;">'.$name.'</span>'
            .($meta !== '' ? '<br><span style="font-size:0.75rem;opacity:0.7;">'.$meta.'</span>' : '')
            .'</span>';
    }

    /** Odošle každému príjemcovi (s emailom) odkaz na private room, ktorému ešte nebol odoslaný. */
    protected static function sendToRecipients(PrivateRoom $record): void
    {
        $recipients = $record->recipients()
            ->wherePivotNull('sent_at')
            ->whereNotNull('contacts.email')
            ->get();

        $sent   = 0;
        $failed = [];

        foreach ($recipients as $contact) {
            // Skip contacts with malformed emails BEFORE calling Mail — an
            // invalid address on ANY single row used to 500 the whole batch.
            if (! filter_var((string) $contact->email, FILTER_VALIDATE_EMAIL)) {
                $failed[] = $contact->display_name.' (invalid e-mail)';
                continue;
            }

            try {
                $html = self::privateRoomEmailHtml($record, $contact);

                Mail::html($html, function ($message) use ($contact, $record) {
                    $message->to($contact->email)
                        ->subject('Private room: '.$record->title);
                });

                $record->recipients()->updateExistingPivot($contact->id, [
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('private-room send failed', [
                    'room'    => $record->id,
                    'contact' => $contact->id,
                    'error'   => $e->getMessage(),
                ]);
                $failed[] = $contact->display_name.' ('.class_basename($e).')';
            }
        }

        if (! $record->sent_at && $sent > 0) {
            $record->update(['sent_at' => now()]);
        }

        $count = $sent;
        $failedCount = count($failed);
        $title = match (true) {
            $count > 0 && $failedCount === 0 => $count.' private room link(s) sent',
            $count > 0 && $failedCount  >  0 => "Sent {$count}, failed {$failedCount}",
            $failedCount > 0                 => 'Failed to send to '.$failedCount.' recipient(s)',
            default                          => 'No new recipients to send to — all those with an email were already contacted.',
        };
        $notification = Notification::make()
            ->title($title)
            ->body(empty($failed) ? null : 'Failed: '.implode(', ', array_slice($failed, 0, 8)).(count($failed) > 8 ? '…' : ''));
        $count > 0 ? $notification->success() : $notification->warning();
        $notification->send();
    }

    /**
     * Zapíše pivot.position pre artworks podľa poradia v poli $artworkIds — používa sa
     * pri ukladaní private room, aby sa zachovalo manuálne usporiadanie pre 'manual' stratégiu.
     */
    public static function updateManualPositions(PrivateRoom $record, array $artworkIds): void
    {
        foreach ($artworkIds as $i => $id) {
            $record->artworks()->updateExistingPivot((int) $id, ['position' => $i + 1]);
        }
    }

    /**
     * HTML telo emailu s odkazom na private room.
     * Ak je vyplnená welcome_message, používa sa ako hlavný text emailu
     * (rovnaký text, ktorý klient uvidí aj v hlavičke roomu).
     * Ak je prázdna, fallback na neutrálnu anglickú vetu.
     * Podpis obsahuje meno galérie a jej kontakt (adresu, telefón, e-mail,
     * web) — všetko z InvoiceSetting::current(), aby email pôsobil ako
     * oficiálna komunikácia galérie.
     */
    protected static function privateRoomEmailHtml(PrivateRoom $record, Contact $contact): string
    {
        $body = filled($record->welcome_message)
            ? '<div style="white-space:pre-wrap;line-height:1.55;">'.nl2br(e($record->welcome_message)).'</div>'
            : '<p>We have prepared a private selection for you.</p>';

        $url = e($record->publicUrl());

        $settings = \App\Models\InvoiceSetting::current();

        // Meno galérie: Gallery vlastnená autorom roomu; fallback na
        // company_name z invoice settings, potom na app.name.
        $galleryName = \App\Models\Gallery::where('owner_user_id', $record->owner_user_id)->value('name')
            ?? $settings?->company_name
            ?? config('app.name');

        // Adresa (jeden riadok) + kontakt (telefón, e-mail, web) —
        // každý údaj zvlášť, len ak je vyplnený.
        $addressLine = trim(collect([
            $settings?->address_line1,
            $settings?->address_line2,
            $settings?->postal_code,
            $settings?->city,
        ])->filter()->implode(', '));

        $signature  = '<p style="margin-top:1.5em;color:#374151;line-height:1.5;">';
        $signature .= 'S pozdravom,<br><strong>'.e($galleryName).'</strong>';
        if ($addressLine !== '') {
            $signature .= '<br>'.e($addressLine);
        }
        if (filled($settings?->phone)) {
            $signature .= '<br>tel: '.e($settings->phone);
        }
        if (filled($settings?->email)) {
            $signature .= '<br>e-mail: <a href="mailto:'.e($settings->email).'">'.e($settings->email).'</a>';
        }
        if (filled($settings?->website)) {
            $signature .= '<br>web: <a href="'.e($settings->website).'">'.e($settings->website).'</a>';
        }
        $signature .= '</p>';

        // Signed unsubscribe/subscribe links — always visible in the
        // footer regardless of the recipient's current status. Handler
        // toggles subscribed_to_newsletter and stamps the timestamp so
        // Kat sees who clicked what in the Contacts table.
        $subscribeUrl   = \Illuminate\Support\Facades\URL::signedRoute('newsletter.action', ['action' => 'subscribe',   'contact' => $contact->id]);
        $unsubscribeUrl = \Illuminate\Support\Facades\URL::signedRoute('newsletter.action', ['action' => 'unsubscribe', 'contact' => $contact->id]);

        $footer  = '<hr style="border:0;border-top:1px solid #e5e7eb;margin:2em 0 1em;">';
        $footer .= '<p style="font-size:0.85em;color:#6b7280;line-height:1.5;">';
        $footer .= 'You are receiving this email because we selected this private room for you.<br>';
        $footer .= '<a href="'.e($subscribeUrl).'" style="color:#374151;">Subscribe for more information</a>';
        $footer .= ' &middot; ';
        $footer .= '<a href="'.e($unsubscribeUrl).'" style="color:#374151;">Unsubscribe</a>';
        $footer .= '</p>';

        // Invisible 1x1 tracking pixel — the first time the recipient's
        // mail client loads this image we stamp opened_at on the pivot.
        $pixelUrl = e(route('email.pixel', ['room' => $record->id, 'contact' => $contact->id]));
        $pixel = '<img src="'.$pixelUrl.'" width="1" height="1" alt="" style="display:block;border:0;width:1px;height:1px;overflow:hidden;">';

        return '<p>Dobrý deň,</p>'
            .$body
            .'<p style="margin-top:1.25em;">View the private room here:<br>'
            .'<a href="'.$url.'">'.$url.'</a></p>'
            .$signature
            .$footer
            .$pixel;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->disk('public')->square()->size(50),
                Tables\Columns\TextColumn::make('title')->searchableAccentless()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('recipients_count')
                    ->counts('recipients')
                    ->label('Recipients')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('artworks_count')->counts('artworks')->label('Artworks'),
                Tables\Columns\IconColumn::make('show_prices')->boolean()->label('Prices'),
                Tables\Columns\TextColumn::make('view_count')->label('Views')->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime('d.m.Y')->sortable()
                    ->placeholder('No expiry'),
                Tables\Columns\TextColumn::make('sent_at')->dateTime('d.m.Y')->placeholder('Not sent')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Public link')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (PrivateRoom $record) => $record->publicUrl())
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PrivateRoomResource\RelationManagers\RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPrivateRooms::route('/'),
            'create' => Pages\CreatePrivateRoom::route('/create'),
            'edit'   => Pages\EditPrivateRoom::route('/{record}/edit'),
        ];
    }

    use \App\Filament\Concerns\OwnerScoped;
}
