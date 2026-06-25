<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CollectionResource::collection(
            Collection::query()
                ->where('is_public', true)
                ->whereNull('parent_id')
                ->withCount('artworks')
                ->orderBy('position')->orderBy('title')
                ->get()
        );
    }

    public function show(Collection $collection): CollectionResource
    {
        abort_unless($collection->is_public, 404);

        $collection->load([
            'artworks' => fn ($q) => $q->where('is_published', true)->with(['artist', 'medium']),
        ]);

        return new CollectionResource($collection);
    }
}
