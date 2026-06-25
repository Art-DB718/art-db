<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Collection;
use App\Models\Contact;
use App\Models\Exhibition;
use App\Models\PrivateRoom;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Full JSON dump of all entities. Admin-only.
     * GET /api/v1/export/full
     */
    public function full(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Admin only.');

        return response()->json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'app'          => config('app.name'),
                'version'      => 'v1',
            ],
            'artists'      => Artist::with('country')->get(),
            'artworks'     => Artwork::with(['artist', 'medium', 'genre', 'status', 'location'])->get(),
            'collections'  => Collection::with('artworks')->get(),
            'exhibitions'  => Exhibition::with('artworks')->get(),
            'sales'        => Sale::with(['buyer', 'lineItems'])->get(),
            'contacts'     => Contact::with(['group', 'country'])->get(),
            'private_rooms'=> PrivateRoom::with(['artworks', 'recipients'])->get(),
            'counts' => [
                'artists'       => Artist::count(),
                'artworks'      => Artwork::count(),
                'collections'   => Collection::count(),
                'exhibitions'   => Exhibition::count(),
                'sales'         => Sale::count(),
                'contacts'      => Contact::count(),
                'private_rooms' => PrivateRoom::count(),
            ],
        ]);
    }
}
