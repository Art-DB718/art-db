<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Exhibition;
use App\Models\InvoiceSetting;
use Illuminate\View\View;

class PlatformController extends Controller
{
    public function index(): View
    {
        return view('public.platform.index', [
            'settings' => InvoiceSetting::current(),
            'counts'   => [
                'artworks'    => Artwork::where('is_published', true)->count(),
                'artists'     => Artist::where('is_published', true)->count(),
                'exhibitions' => Exhibition::where('is_published', true)->count(),
            ],
        ]);
    }

    public function gallery(): View
    {
        return view('public.platform.gallery', [
            'settings' => InvoiceSetting::current(),
        ]);
    }

    public function artist(): View
    {
        return view('public.platform.artist', [
            'settings' => InvoiceSetting::current(),
        ]);
    }

    public function collector(): View
    {
        return view('public.platform.collector', [
            'settings' => InvoiceSetting::current(),
        ]);
    }
}
