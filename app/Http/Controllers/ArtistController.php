<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Country;
use App\Models\Exhibition;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(Request $request)
    {
        $query = Artist::query()
            ->with(['country', 'galleries'])
            ->where('is_published', true);

        if ($request->filled('country_id')) {
            $query->where('country_id', (int) $request->country_id);
        }
        if ($request->filled('gallery_id')) {
            $query->whereHas('galleries', fn ($q) => $q->whereKey((int) $request->gallery_id));
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

        return view('public.artists.index', [
            'artists'   => $artists,
            'countries' => Country::orderBy('name')->get(['id', 'name']),
            'galleries' => \App\Models\Gallery::where('is_published', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Artist $artist)
    {
        abort_unless($artist->is_published, 404);

        $artist->load(['country', 'galleries']);

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
}
