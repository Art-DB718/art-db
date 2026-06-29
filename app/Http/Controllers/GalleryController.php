<?php

namespace App\Http\Controllers;

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

        return view('public.galleries.show', compact('gallery'));
    }
}
