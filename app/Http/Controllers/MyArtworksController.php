<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyArtworksController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isArtist(), 403);

        $artworks = Artwork::with(['medium', 'status'])
            ->where('owner_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('my.artworks', [
            'artworks' => $artworks,
        ]);
    }
}
