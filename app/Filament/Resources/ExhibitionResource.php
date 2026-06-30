<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExhibitionResource\Pages;
use App\Filament\Resources\ExhibitionResource\RelationManagers;
use App\Models\Contact;
use App\Models\Exhibition;
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

class ExhibitionResource extends Resource
{
    protected static ?string $model = Exhibition::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Exhibitions & Movements';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public const TYPES = [
        'solo'     => 'Solo',
        'group'    => 'Group',
        'art_fair' => 'Art fair',
        'online'   => 'Online',
        'museum'   => 'Museum',
    ];

    public const STATUSES = [
        'upcoming'  => 'Upcoming',
        'current'   => 'Current',
        'past'      => 'Past',
        'cancelled' => 'Cancelled',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Exhibition')->tabs([

                Forms\Components\Tabs\Tab::make('Details')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpan(2),
                        Forms\Components\Select::make('type')->options(self::TYPES)->default('group')->required(),
                        Forms\Components\Select::make('status')->options(self::STATUSES)->default('upcoming')->required(),
                        Forms\Components\TextInput::make('venue')->maxLength(255),
                        Forms\Components\Select::make('location_id')
                            ->relationship('location', 'name')->searchable()->preload(),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                        Forms\Components\DateTimePicker::make('opening_at')->label('Opening (date & time)'),
                        Forms\Components\TextInput::make('curator')->maxLength(255),
                    ]),
                    Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
                    Forms\Components\Textarea::make('press_release')->rows(4)->columnSpanFull(),
                ]),

                Forms\Components\Tabs\Tab::make('Media')->schema([
                    Forms\Components\FileUpload::make('poster_image')
                        ->image()
                        ->disk('public')
                        ->directory('exhibitions')
                        ->helperText('Public site shows the poster as a 3:1 banner on the exhibition page (full-width hero) and as a 4:3 thumbnail in listings. Both crops use object-cover, so subject should sit near the centre. Recommended: landscape, at least 1920 × 800 px.'),
                    Forms\Components\FileUpload::make('gallery_images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->disk('public')
                        ->directory('exhibitions/gallery')
                        ->helperText('Installation views appear in a 3-column grid, each cropped to a 4:3 thumbnail; clicking opens the full image in a new tab. Upload landscape format ≥ 1600 × 1200 px for the cleanest crop; portraits and squares get centre-cropped.'),
                ]),

                Forms\Components\Tabs\Tab::make('Publishing')->schema([
                    Forms\Components\Toggle::make('is_published')->label('Published on public site'),
                ]),

                Forms\Components\Tabs\Tab::make('Invites')
                    ->icon('heroicon-m-envelope')
                    ->schema([
                        Forms\Components\Select::make('invitedContacts')
                            ->label('Invited guests')
                            ->relationship('invitedContacts', 'last_name')
                            ->multiple()
                            ->searchable(['first_name', 'last_name', 'organization', 'email'])
                            ->preload()
                            ->allowHtml()
                            ->getOptionLabelFromRecordUsing(fn (Contact $record): string => self::contactInviteLabel($record))
                            ->helperText('Pick guests from the contact list. Save the exhibition, then use "Send invitations" below.')
                            ->columnSpanFull(),
                        Forms\Components\Section::make('Invitation email')
                            ->description('The email shows the exhibition poster at the top, then this message, then the curator and the exhibition description. Placeholders are replaced automatically for each guest.')
                            ->icon('heroicon-m-pencil-square')
                            ->schema([
                                Forms\Components\RichEditor::make('invitation_message')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (?string $state): string => filled($state)
                                        ? $state
                                        : self::defaultInvitationTemplate())
                                    ->helperText(new HtmlString('Placeholders: <code>{{guest_name}}</code>, '
                                        .'<code>{{exhibition_title}}</code>, <code>{{venue}}</code>, '
                                        .'<code>{{dates}}</code>, <code>{{opening}}</code>'))
                                    ->columnSpanFull(),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('previewInvitation')
                                        ->label('Preview email')
                                        ->icon('heroicon-m-eye')
                                        ->color('gray')
                                        ->modalHeading('Invitation email preview')
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Close')
                                        ->modalContent(function (Forms\Get $get, ?Exhibition $record): HtmlString {
                                            $template = filled($get('invitation_message'))
                                                ? $get('invitation_message')
                                                : self::defaultInvitationTemplate();
                                            $guest = $record?->invitedContacts()->first()?->display_name ?? 'Guest name';
                                            $body = self::renderInvitation($template, $record, $guest);

                                            return new HtmlString(
                                                '<div style="background:#ffffff;color:#1f2937;padding:1.5rem;'
                                                .'border-radius:0.5rem;line-height:1.55;">'
                                                .'<div style="font-size:0.8rem;color:#6b7280;margin-bottom:0.5rem;">'
                                                .'<strong>Subject:</strong> Invitation: '.e($record?->title ?? '').'</div>'
                                                .'<hr style="border:0;border-top:1px solid #e5e7eb;margin:0 0 1rem;">'
                                                .$body
                                                .'</div>'
                                            );
                                        }),
                                ]),
                            ]),
                        Forms\Components\Placeholder::make('invite_stats')
                            ->label('Invitation status')
                            ->content(fn (?Exhibition $record): string => $record
                                ? $record->invitedContacts()->count().' invited · '
                                    .$record->invitedContacts()->wherePivot('status', 'sent')->count().' already sent'
                                : 'Save the exhibition first to manage and send invitations.'),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('sendInvitations')
                                ->label('Send invitations')
                                ->icon('heroicon-m-paper-airplane')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalDescription('Sends an invitation email to every invited guest who has an email address and has not received one yet.')
                                ->visible(fn (?Exhibition $record): bool => $record !== null)
                                ->action(fn (Exhibition $record) => self::sendInvitations($record)),
                        ]),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    /** HTML menovka kontaktu pre Select pozvaných hostí — meno + organizácia/email. */
    protected static function contactInviteLabel(Contact $record): string
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

    /** Odošle pozvánku každému pozvanému hosťovi s emailom, ktorému ešte nebola odoslaná. */
    protected static function sendInvitations(Exhibition $record): void
    {
        $template = filled($record->invitation_message)
            ? $record->invitation_message
            : self::defaultInvitationTemplate();

        $recipients = $record->invitedContacts()
            ->wherePivotNull('sent_at')
            ->whereNotNull('contacts.email')
            ->get();

        foreach ($recipients as $contact) {
            $html = self::renderInvitation($template, $record, $contact->display_name);

            Mail::html($html, function ($message) use ($contact, $record) {
                $message->to($contact->email)
                    ->subject('Invitation: '.$record->title);
            });

            $record->invitedContacts()->updateExistingPivot($contact->id, [
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }

        $count = $recipients->count();
        $notification = Notification::make()
            ->title($count > 0
                ? $count.' invitation(s) sent'
                : 'No new invitations to send — all invited guests with an email were already contacted.');
        $count > 0 ? $notification->success() : $notification->warning();
        $notification->send();
    }

    /** Predvolená šablóna pozývacieho emailu (s placeholdermi). */
    protected static function defaultInvitationTemplate(): string
    {
        return '<p>Dear {{guest_name}},</p>'
            .'<p>We would like to invite you to the exhibition <strong>{{exhibition_title}}</strong>.</p>'
            .'<p><strong>Venue:</strong> {{venue}}<br>'
            .'<strong>Dates:</strong> {{dates}}<br>'
            .'<strong>Opening:</strong> {{opening}}</p>';
    }

    /**
     * Poskladá pozývací email: plagát výstavy hore, pod ním editovateľný text
     * (s nahradenými placeholdermi), meno kurátora a popis výstavy.
     */
    protected static function renderInvitation(string $template, ?Exhibition $exhibition, string $guestName): string
    {
        $dates = $exhibition
            ? collect([
                $exhibition->start_date?->format('d.m.Y'),
                $exhibition->end_date?->format('d.m.Y'),
            ])->filter()->implode(' – ')
            : '';

        $message = strtr($template, [
            '{{guest_name}}'       => e($guestName),
            '{{exhibition_title}}' => e($exhibition?->title ?? ''),
            '{{venue}}'            => e($exhibition?->venue ?? '—'),
            '{{dates}}'            => e($dates !== '' ? $dates : '—'),
            '{{opening}}'          => e($exhibition?->opening_at?->format('d.m.Y H:i') ?? '—'),
        ]);

        $html = '';

        // 1) Plagát výstavy
        if ($exhibition?->poster_image) {
            $html .= '<img src="'.e(Storage::url($exhibition->poster_image)).'" '
                .'alt="'.e($exhibition->title ?? '').'" '
                .'style="width:100%;max-width:600px;height:auto;border-radius:8px;'
                .'display:block;margin:0 0 1.25rem;">';
        }

        // 2) Editovateľný text pozvánky
        $html .= $message;

        // 3) Kurátor
        if (filled($exhibition?->curator)) {
            $html .= '<p style="margin-top:1.25rem;"><strong>Curator:</strong> '
                .e($exhibition->curator).'</p>';
        }

        // 4) Popis výstavy
        if (filled($exhibition?->description)) {
            $html .= '<div style="margin-top:1rem;color:#4b5563;">'
                .nl2br(e($exhibition->description)).'</div>';
        }

        // 5) Záverečný pozdrav na konci emailu
        $html .= '<p style="margin-top:1.25rem;">We look forward to welcoming you.</p>';

        return $html;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('poster_image')->disk('public')->square()->size(50),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (?string $state) => self::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('venue')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (?string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'current'   => 'success',
                        'upcoming'  => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('artworks_count')->counts('artworks')->label('Artworks'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Public'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::TYPES),
                Tables\Filters\SelectFilter::make('status')->options(self::STATUSES),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->defaultSort('start_date', 'desc')
            ->actions([
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
            RelationManagers\ArtworksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExhibitions::route('/'),
            'create' => Pages\CreateExhibition::route('/create'),
            'edit'   => Pages\EditExhibition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
