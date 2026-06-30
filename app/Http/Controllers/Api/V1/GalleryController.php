<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ArtistResource;
use App\Http\Resources\V1\GalleryResource;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Gallery::query()
            ->where('is_published', true)
            ->with('country')
            ->withCount('artists');

        if ($request->filled('q')) {
            $query->where('name', 'ilike', '%'.$request->q.'%');
        }

        return GalleryResource::collection(
            $query->orderBy('name')->paginate(min((int) $request->input('per_page', 24), 100))
        );
    }

    public function show(Gallery $gallery)
    {
        abort_unless($gallery->is_published, 404);
        $gallery->load('country')->loadCount('artists');
        return new GalleryResource($gallery);
    }

    public function artists(Gallery $gallery, Request $request)
    {
        abort_unless($gallery->is_published, 404);

        return ArtistResource::collection(
            $gallery->artists()
                ->where('is_published', true)
                ->with('country')
                ->orderBy('last_name')->orderBy('first_name')
                ->paginate(min((int) $request->input('per_page', 24), 100))
        );
    }
}
