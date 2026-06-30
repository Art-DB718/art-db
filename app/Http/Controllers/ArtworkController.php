<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\ArtworkStatus;
use App\Models\Contact;
use App\Models\Genre;
use App\Models\InvoiceSetting;
use App\Models\Medium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $query = Artwork::query()
            ->with(['artist', 'medium', 'status'])
            ->where('is_published', true);

        if ($request->filled('artist_id')) {
            $query->where('artist_id', (int) $request->artist_id);
        }
        if ($request->filled('medium_id')) {
            $query->where('medium_id', (int) $request->medium_id);
        }
        if ($request->filled('genre_id')) {
            $query->where('genre_id', (int) $request->genre_id);
        }
        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->status_id);
        }
        if ($request->filled('gallery_id')) {
            $query->whereHas('artist.galleries', fn ($g) => $g->whereKey((int) $request->gallery_id));
        }
        if ($request->filled('year_from')) {
            $query->where('year_created', '>=', (int) $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('year_created', '<=', (int) $request->year_to);
        }
        if ($request->filled('price_from')) {
            $query->where('price', '>=', (float) $request->price_from);
        }
        if ($request->filled('price_to')) {
            $query->where('price', '<=', (float) $request->price_to);
        }
        if ($request->filled('q')) {
            $needle = '%'.$request->q.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'ilike', $needle)
                  ->orWhereHas('artist', fn ($a) => $a
                      ->where('last_name', 'ilike', $needle)
                      ->orWhere('first_name', 'ilike', $needle));
            });
        }
        if ($request->filled('tag')) {
            // PostgreSQL: tag stored as JSON array; check membership with ?
            $query->whereJsonContains('tags', (string) $request->tag);
        }

        $allowedPerPage = [20, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $allowedViews = ['cards', 'gallery', 'list'];
        $view = (string) $request->input('view', 'cards');
        if (! in_array($view, $allowedViews, true)) {
            $view = 'cards';
        }

        $artworks = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('public.artworks.index', [
            'artworks'       => $artworks,
            'perPage'        => $perPage,
            'perPageOptions' => $allowedPerPage,
            'view'           => $view,
            'viewOptions'    => $allowedViews,
            'artists'        => Artist::where('is_published', true)->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'mediums'        => Medium::orderBy('name')->get(['id', 'name']),
            'genres'         => Genre::orderBy('name')->get(['id', 'name']),
            'statuses'       => ArtworkStatus::where('is_public', true)->orderBy('position')->get(['id', 'name']),
            'galleries'      => \App\Models\Gallery::where('is_published', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Artwork $artwork)
    {
        abort_unless($artwork->is_published, 404);

        $artwork->load(['artist', 'medium', 'genre', 'status']);

        $related = Artwork::with('artist')
            ->where('is_published', true)
            ->where('artist_id', $artwork->artist_id)
            ->where('id', '!=', $artwork->id)
            ->limit(4)
            ->get();

        return view('public.artworks.show', [
            'artwork' => $artwork,
            'related' => $related,
        ]);
    }

    public function inquire(Artwork $artwork, Request $request)
    {
        abort_unless($artwork->is_published, 404);

        $user = $request->user();

        // Auth visitors don't need to retype name/email (taken from User).
        $rules = [
            'message'              => ['required', 'string', 'max:2000'],
            'subscribe_newsletter' => ['nullable', 'boolean'],
        ];
        if (! $user) {
            $rules['name']  = ['required', 'string', 'max:255'];
            $rules['email'] = ['required', 'email', 'max:255'];
        }
        $validated = $request->validate($rules);
        $wantsNewsletter = (bool) ($validated['subscribe_newsletter'] ?? false);

        // Resolve recipient — artwork owner, or fall back to gallery contact email.
        $recipientUserId = $artwork->owner_user_id;
        $settings        = InvoiceSetting::current();
        $fallbackEmail   = $settings->email ?: config('mail.from.address');

        // Create the Inquiry record. Filament inbox will pick it up automatically.
        $inquiry = \App\Models\Inquiry::create([
            'sender_user_id'    => $user?->id,
            'guest_name'        => $user ? null : ($validated['name'] ?? null),
            'guest_email'       => $user ? null : ($validated['email'] ?? null),
            'recipient_user_id' => $recipientUserId,
            'artwork_id'        => $artwork->id,
            'message'           => $validated['message'],
            'status'            => 'new',
        ]);
        $inquiry->load(['sender', 'recipient', 'artwork.artist']);

        // CRM contact + optional newsletter opt-in.
        $contactEmail = $user?->email ?: ($validated['email'] ?? null);
        $contactName  = $user?->name  ?: ($validated['name']  ?? null);

        if ($contactEmail) {
            $names = $contactName ? preg_split('/\s+/', trim($contactName), 2) : [null, null];
            $contact = Contact::firstOrCreate(
                ['email' => $contactEmail],
                [
                    'first_name' => $names[0] ?? null,
                    'last_name'  => $names[1] ?? null,
                    'source'     => 'artwork inquiry',
                    'notes'      => 'Inquiry about: '.$artwork->title,
                ],
            );

            // Explicit opt-in only — GDPR requires affirmative consent, so we
            // never auto-subscribe just because someone sent an inquiry.
            if ($wantsNewsletter && ! $contact->subscribed_to_newsletter) {
                $contact->update(['subscribed_to_newsletter' => true]);
                try {
                    app(\App\Services\MailchimpService::class)
                        ->subscribe($contactEmail, $names[0] ?? null, $names[1] ?? null);
                } catch (\Throwable $e) {
                    logger()->warning('Mailchimp opt-in from inquiry failed for '.$contactEmail.': '.$e->getMessage());
                }
            }
        }

        // Email the recipient via the InquiryReceived mailable (Mail::log driver in dev,
        // Resend in prod once RESEND_API_KEY is set + MAIL_MAILER=resend).
        $recipientEmail = $artwork->owner?->email ?: $fallbackEmail;
        if ($recipientEmail) {
            try {
                Mail::to($recipientEmail)->send(new \App\Mail\InquiryReceived($inquiry));
            } catch (\Throwable $e) {
                logger()->warning('Inquiry mail failed: '.$e->getMessage());
            }
        }

        return redirect()
            ->route('artworks.show', $artwork)
            ->with('inquiry_message', 'Thank you — your inquiry has been sent. We’ll be in touch shortly.');
    }
}
