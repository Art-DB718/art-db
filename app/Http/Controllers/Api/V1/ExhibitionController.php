<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ExhibitionResource;
use App\Models\Exhibition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExhibitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exhibition::query()->where('is_published', true);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        return ExhibitionResource::collection(
            $query->orderByDesc('start_date')->paginate($perPage)->withQueryString()
        );
    }

    public function show(Exhibition $exhibition): ExhibitionResource
    {
        abort_unless($exhibition->is_published, 404);
        $exhibition->load([
            'artworks' => fn ($q) => $q->where('is_published', true)->with(['artist', 'medium']),
        ]);
        return new ExhibitionResource($exhibition);
    }
}
