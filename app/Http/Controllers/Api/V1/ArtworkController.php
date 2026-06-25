<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ArtworkResource;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtworkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Artwork::query()
            ->with(['artist', 'medium', 'genre', 'status'])
            ->where('is_published', true);

        if ($request->filled('artist_id')) $query->where('artist_id', (int) $request->artist_id);
        if ($request->filled('medium_id')) $query->where('medium_id', (int) $request->medium_id);
        if ($request->filled('genre_id'))  $query->where('genre_id', (int) $request->genre_id);
        if ($request->filled('status_id')) $query->where('status_id', (int) $request->status_id);
        if ($request->filled('year_from')) $query->where('year_created', '>=', (int) $request->year_from);
        if ($request->filled('year_to'))   $query->where('year_created', '<=', (int) $request->year_to);
        if ($request->filled('price_from')) $query->where('price', '>=', (float) $request->price_from);
        if ($request->filled('price_to'))   $query->where('price', '<=', (float) $request->price_to);
        if ($request->filled('q')) {
            $needle = '%'.$request->q.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'ilike', $needle)
                  ->orWhereHas('artist', fn ($a) => $a
                      ->where('last_name', 'ilike', $needle)
                      ->orWhere('first_name', 'ilike', $needle));
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return ArtworkResource::collection(
            $query->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(Artwork $artwork): ArtworkResource
    {
        abort_unless($artwork->is_published, 404);
        $artwork->load(['artist', 'medium', 'genre', 'status']);
        return new ArtworkResource($artwork);
    }
}
