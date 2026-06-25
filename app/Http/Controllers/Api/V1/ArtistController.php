<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ArtistResource;
use App\Http\Resources\V1\ArtworkResource;
use App\Models\Artist;
use App\Models\Artwork;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Artist::query()->with('country')->where('is_published', true);

        if ($request->filled('country_id')) $query->where('country_id', (int) $request->country_id);
        if ($request->filled('status')) {
            $request->status === 'living'
                ? $query->whereNull('death_year')
                : ($request->status === 'deceased' ? $query->whereNotNull('death_year') : null);
        }
        if ($request->filled('q')) {
            $needle = '%'.$request->q.'%';
            $query->where(fn ($q) => $q
                ->where('last_name', 'ilike', $needle)
                ->orWhere('first_name', 'ilike', $needle));
        }

        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        return ArtistResource::collection(
            $query->orderByDesc('is_featured')
                ->orderBy('last_name')->orderBy('first_name')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(Artist $artist): ArtistResource
    {
        abort_unless($artist->is_published, 404);
        $artist->load('country');
        return new ArtistResource($artist);
    }

    /** GET /api/v1/artists/{slug}/artworks */
    public function artworks(Artist $artist, Request $request): AnonymousResourceCollection
    {
        abort_unless($artist->is_published, 404);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $works = Artwork::with(['artist', 'medium', 'status'])
            ->where('artist_id', $artist->id)
            ->where('is_published', true)
            ->orderByDesc('year_created')
            ->paginate($perPage);

        return ArtworkResource::collection($works);
    }
}
