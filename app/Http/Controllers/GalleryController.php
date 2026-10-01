<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Exhibition;
use App\Models\Gallery;
use Illuminate\Support\Carbon;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::query()
            ->where('is_published', true)
            ->withCount(['artists'])
            ->orderByDesc('artists_count')
            ->orderBy('name')
            ->paginate(20);

        return view('public.galleries.index', compact('galleries'));
    }

    public function show(Gallery $gallery)
    {
        abort_unless($gallery->is_published, 404);
        $gallery->load(['country', 'artists' => fn ($q) => $q->where('is_published', true)]);

        $representedIds = $gallery->artists->pluck('id');

        // "Presented artworks" — union of:
        //   - works uploaded by the gallery user (owner_user_id = gallery.owner_user_id)
        //   - works by artists this gallery represents (artist_gallery pivot)
        $artworks = Artwork::query()
            ->where('is_published', true)
            ->where(function ($q) use ($gallery, $representedIds) {
                $q->where('owner_user_id', $gallery->owner_user_id);
                if ($representedIds->isNotEmpty()) {
                    $q->orWhereIn('artist_id', $representedIds);
                }
            })
            ->with(['artist:id,slug,first_name,last_name'])
            ->latest()
            ->take(24)
            ->get();

        // "Also showing works by" — artists NOT represented by this gallery,
        // but whose works this gallery has uploaded. Bridges the case where
        // a gallery hosts / features an artist without formally repping them.
        $alsoShowingArtistIds = Artwork::query()
            ->where('is_published', true)
            ->where('owner_user_id', $gallery->owner_user_id)
            ->whereNotIn('artist_id', $representedIds)
            ->distinct()
            ->pluck('artist_id');

        $alsoShowing = $alsoShowingArtistIds->isEmpty()
            ? collect()
            : Artist::query()
                ->whereIn('id', $alsoShowingArtistIds)
                ->where('is_published', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

        // "Presented artists" — artists this gallery user OWNS and has flagged
        // as featured (is_featured). Same as marking an artist to publish on
        // the home page. Shown on the gallery's public profile.
        $presentedArtists = Artist::query()
            ->where('owner_user_id', $gallery->owner_user_id)
            ->where('is_published', true)
            ->where('is_featured', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Exhibitions by this gallery, grouped: current / upcoming / past.
        // Timeline is derived from dates (not the status enum alone) so
        // forgotten "upcoming" rows that have already started still land
        // in the correct bucket. Cancelled rows are excluded.
        $today = Carbon::today();
        $exhibitions = Exhibition::query()
            ->where('owner_user_id', $gallery->owner_user_id)
            ->where('is_published', true)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date', 'desc')
            ->get();

        $currentExhibitions = $exhibitions->filter(fn ($e) =>
            ($e->start_date && $e->start_date <= $today) &&
            (! $e->end_date || $e->end_date >= $today)
        )->values();
        $upcomingExhibitions = $exhibitions->filter(fn ($e) =>
            $e->start_date && $e->start_date > $today
        )->sortBy('start_date')->values();
        $pastExhibitions = $exhibitions->filter(fn ($e) =>
            $e->end_date && $e->end_date < $today
        )->values();

        return view('public.galleries.show', compact(
            'gallery', 'artworks', 'alsoShowing', 'presentedArtists',
            'currentExhibitions', 'upcomingExhibitions', 'pastExhibitions',
        ));
    }
}
