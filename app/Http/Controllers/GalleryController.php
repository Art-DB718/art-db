<?php

namespace App\Http\Controllers;

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

        // "Presented artworks" — union of:
        //   - works uploaded by the gallery user (owner_user_id = gallery.owner_user_id)
        //   - works by artists this gallery represents (artist_gallery pivot)
        $artistIds = $gallery->artists->pluck('id');

        $artworks = Artwork::query()
            ->where('is_published', true)
            ->where(function ($q) use ($gallery, $artistIds) {
                $q->where('owner_user_id', $gallery->owner_user_id);
                if ($artistIds->isNotEmpty()) {
                    $q->orWhereIn('artist_id', $artistIds);
                }
            })
            ->with(['artist:id,slug,first_name,last_name'])
            ->latest()
            ->take(24)
            ->get();

        return view('public.galleries.show', compact('gallery', 'artworks'));
    }
}
