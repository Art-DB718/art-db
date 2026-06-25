<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::query()
            ->where('is_public', true)
            ->whereNull('parent_id')
            ->withCount('artworks')
            ->orderBy('position')
            ->orderBy('title')
            ->get();

        return view('public.collections.index', [
            'collections' => $collections,
        ]);
    }

    public function show(Collection $collection)
    {
        abort_unless($collection->is_public, 404);

        $collection->load([
            'artworks' => fn ($q) => $q->where('is_published', true)->with(['artist', 'medium']),
            'children' => fn ($q) => $q->where('is_public', true)->orderBy('position'),
        ]);

        return view('public.collections.show', [
            'collection' => $collection,
        ]);
    }
}
