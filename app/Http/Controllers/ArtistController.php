<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Country;
use App\Models\Exhibition;
use App\Models\University;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(Request $request)
    {
        $query = Artist::query()
            ->with(['country', 'university'])
            ->where('is_published', true);

        if ($request->filled('country_id')) {
            $query->where('country_id', (int) $request->country_id);
        }
        if ($request->filled('university_id')) {
            $query->where('university_id', (int) $request->university_id);
        }
        if ($request->filled('q')) {
            $needle = '%'.$request->q.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('last_name', 'ilike', $needle)
                  ->orWhere('first_name', 'ilike', $needle);
            });
        }

        $artists = $query
            ->orderByDesc('is_featured')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(24)
            ->withQueryString();

        // Only universities that have at least one published student show up in the filter.
        $universities = University::query()
            ->whereHas('artists', fn ($q) => $q->where('is_published', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('public.artists.index', [
            'artists'      => $artists,
            'countries'    => Country::orderBy('name')->get(['id', 'name']),
            'universities' => $universities,
        ]);
    }

    public function show(Artist $artist)
    {
        abort_unless($artist->is_published, 404);

        $artist->load(['country', 'university']);

        $artworks = Artwork::with(['medium', 'status'])
            ->where('is_published', true)
            ->where('artist_id', $artist->id)
            ->orderByDesc('is_featured')
            ->orderByDesc('year_created')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // Exhibitions derived through this artist's artworks.
        $exhibitions = Exhibition::query()
            ->where('is_published', true)
            ->whereHas('artworks', fn ($q) => $q->where('artist_id', $artist->id))
            ->orderByDesc('start_date')
            ->limit(6)
            ->get();

        return view('public.artists.show', [
            'artist'      => $artist,
            'artworks'    => $artworks,
            'exhibitions' => $exhibitions,
        ]);
    }

    /** POST /artists/{slug}/contact — Gallery/Collector → message to the artist via the gallery email. */
    public function contact(Artist $artist, Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($artist->is_published, 404);
        $user = $request->user();
        abort_unless($user && ! $user->isArtist(), 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $settings = \App\Models\InvoiceSetting::current();
        $to = $settings->email ?: config('mail.from.address');

        $senderLabel = $user->institution_name ?: $user->name;
        $html = '<p>New contact request for artist <strong>'.e($artist->display_name).'</strong>.</p>'
            .'<p><strong>From:</strong> '.e($senderLabel).' &lt;'.e($user->email).'&gt; ('.e($user->role->label()).')</p>'
            .'<p><strong>Subject:</strong> '.e($data['subject']).'</p>'
            .'<p><strong>Message:</strong></p>'
            .'<blockquote style="border-left:3px solid #d1d5db;padding-left:1rem;color:#374151;">'
            .nl2br(e($data['message']))
            .'</blockquote>';

        try {
            \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($to, $user, $artist, $data) {
                $m->to($to)
                  ->replyTo($user->email, $user->name)
                  ->subject('Contact request for '.$artist->display_name.': '.$data['subject']);
            });
        } catch (\Throwable $e) {
            logger()->warning('Artist contact mail failed: '.$e->getMessage());
        }

        return back()->with('inquiry_message', 'Your message has been sent. The gallery will forward it to '.$artist->display_name.'.');
    }
}
