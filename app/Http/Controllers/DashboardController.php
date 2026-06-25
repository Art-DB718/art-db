<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Collection;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        // Onboarding fallback — ak user ešte nedokončil setup, posuň ho tam.
        if (! $user->onboarded_at && $user->role) {
            return redirect()->route('onboarding.show', ['role' => $user->role->value]);
        }

        // Admin má admin panel ako "home".
        if ($user->isAdmin()) {
            return redirect('/admin');
        }

        // Gallery — institutional browser, custom dashboard.
        if ($user->isGallery()) {
            $savedArtworks = $user->savedArtworks()->with(['artist', 'medium'])->latest('artwork_saves.created_at')->limit(8)->get();
            $likedArtworks = $user->likedArtworks()->with(['artist'])->latest('artwork_likes.created_at')->limit(8)->get();

            return view('dashboard.gallery', compact('user', 'savedArtworks', 'likedArtworks'));
        }

        // University — represents an art school. Students appear automatically,
        // and the university can organise exhibitions.
        if ($user->isUniversity()) {
            $user->load('university');
            $students = \App\Models\Artist::where('university_id', $user->university_id)
                ->where('is_published', true)
                ->orderByDesc('is_featured')
                ->orderBy('last_name')
                ->get();
            $exhibitions = \App\Models\Exhibition::where('university_id', $user->university_id)
                ->orderByDesc('start_date')
                ->limit(6)
                ->get();

            return view('dashboard.university', compact('user', 'students', 'exhibitions'));
        }

        if ($user->isArtist()) {
            $profile     = $user->artistProfile;
            $artworks    = Artwork::where('owner_user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
            $totalCount  = Artwork::where('owner_user_id', $user->id)->count();
            $publishedCount = Artwork::where('owner_user_id', $user->id)->where('is_published', true)->count();

            $collections = Collection::where('owner_user_id', $user->id)
                ->withCount('artworks')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();

            return view('dashboard.artist', compact('user', 'profile', 'artworks', 'totalCount', 'publishedCount', 'collections'));
        }

        if ($user->isCollector()) {
            $savedArtworks = $user->savedArtworks()->with(['artist', 'medium'])->latest('artwork_saves.created_at')->limit(8)->get();
            $likedArtworks = $user->likedArtworks()->with(['artist'])->latest('artwork_likes.created_at')->limit(8)->get();
            $purchasedArtworks = collect();

            return view('dashboard.collector', compact('user', 'purchasedArtworks', 'savedArtworks', 'likedArtworks'));
        }

        abort(403);
    }
}
