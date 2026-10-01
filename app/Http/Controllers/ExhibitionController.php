<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;

class ExhibitionController extends Controller
{
    public function index()
    {
        $current = Exhibition::query()
            ->where('is_published', true)
            ->where('status', 'current')
            ->orderByDesc('start_date')
            ->get();

        $upcoming = Exhibition::query()
            ->where('is_published', true)
            ->where('status', 'upcoming')
            ->orderBy('start_date')
            ->get();

        $past = Exhibition::query()
            ->where('is_published', true)
            ->where('status', 'past')
            ->orderByDesc('end_date')
            ->paginate(12)
            ->withQueryString();

        return view('public.exhibitions.index', [
            'current'  => $current,
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    public function show(Exhibition $exhibition)
    {
        abort_unless($exhibition->is_published, 404);

        $exhibition->load([
            'location',
            'artworks' => fn ($q) => $q->where('is_published', true)->with(['artist', 'medium']),
        ]);

        // Hosting gallery = the Gallery row owned by whoever created the
        // exhibition. Used to turn the Venue line into a link to the
        // gallery's public profile when published.
        $hostingGallery = \App\Models\Gallery::query()
            ->where('owner_user_id', $exhibition->owner_user_id)
            ->where('is_published', true)
            ->first();

        return view('public.exhibitions.show', [
            'exhibition'     => $exhibition,
            'hostingGallery' => $hostingGallery,
        ]);
    }
}
