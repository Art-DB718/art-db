<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Gallery;

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

        return view('public.galleries.show', compact('gallery', 'artworks', 'alsoShowing'));
    }
}
