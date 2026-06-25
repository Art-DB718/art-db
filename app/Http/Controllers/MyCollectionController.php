<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MyCollectionController extends Controller
{
    /** GET /my/collection — list of saved artworks. */
    public function index(): View
    {
        $user = Auth::user();
        abort_if($user->isArtist(), 403, 'Artists do not have a personal collection.');

        $artworks = $user->savedArtworks()
            ->with(['artist', 'medium'])
            ->orderByDesc('artwork_saves.created_at')
            ->paginate(20);

        return view('my.collection', compact('artworks'));
    }

    /** POST /artworks/{artwork:slug}/save — toggle save. */
    public function toggleSave(Artwork $artwork, Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user->isArtist(), 403);

        if ($user->savedArtworks()->where('artwork_id', $artwork->id)->exists()) {
            $user->savedArtworks()->detach($artwork->id);
            $msg = 'Removed from your collection.';
        } else {
            $user->savedArtworks()->attach($artwork->id);
            $msg = 'Saved to your collection.';
        }

        return back()->with('inquiry_message', $msg);
    }

    /** POST /artworks/{artwork:slug}/like — toggle like. */
    public function toggleLike(Artwork $artwork, Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user->isArtist(), 403);

        if ($user->likedArtworks()->where('artwork_id', $artwork->id)->exists()) {
            $user->likedArtworks()->detach($artwork->id);
        } else {
            $user->likedArtworks()->attach($artwork->id);
        }

        return back();
    }
}
