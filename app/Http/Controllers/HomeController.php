<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Exhibition;
use App\Models\University;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('public.home', [
            'featuredArtworks' => Artwork::with('artist')
                ->where('is_published', true)
                ->where('is_featured', true)
                ->limit(6)
                ->orderByDesc('created_at')
                ->get(),
            'featuredArtists' => Artist::where('is_published', true)
                ->where('is_featured', true)
                ->limit(10)
                ->orderBy('last_name')
                ->get(),
            'exhibitions' => Exhibition::where('is_published', true)
                ->whereIn('status', ['upcoming', 'current'])
                ->orderBy('start_date')
                ->limit(4)
                ->get(),
            'universities' => University::query()
                ->withCount(['artists' => fn ($q) => $q->where('is_published', true)])
                ->whereHas('artists', fn ($q) => $q->where('is_published', true))
                ->orderByDesc('artists_count')
                ->limit(8)
                ->get(),
        ]);
    }
}
