<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Collection;
use App\Models\Exhibition;
use App\Models\Gallery;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect();

        // Static routes
        $now = Carbon::now()->toAtomString();
        foreach (['home', 'artworks.index', 'artists.index', 'galleries.index', 'collections.index', 'exhibitions.index', 'platform'] as $name) {
            $urls->push([
                'loc'        => route($name),
                'lastmod'    => $now,
                'changefreq' => 'weekly',
                'priority'   => $name === 'home' ? '1.0' : '0.8',
            ]);
        }

        // Galleries
        Gallery::query()->where('is_published', true)
            ->select('slug', 'updated_at')
            ->each(function ($g) use ($urls) {
                $urls->push([
                    'loc'        => route('galleries.show', $g->slug),
                    'lastmod'    => $g->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.7',
                ]);
            });

        // Artworks
        Artwork::query()->where('is_published', true)
            ->select('slug', 'updated_at')
            ->chunk(500, function ($chunk) use ($urls) {
                foreach ($chunk as $a) {
                    $urls->push([
                        'loc'        => route('artworks.show', $a->slug),
                        'lastmod'    => $a->updated_at?->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority'   => '0.7',
                    ]);
                }
            });

        // Artists
        Artist::query()->where('is_published', true)
            ->select('slug', 'updated_at')
            ->chunk(500, function ($chunk) use ($urls) {
                foreach ($chunk as $a) {
                    $urls->push([
                        'loc'        => route('artists.show', $a->slug),
                        'lastmod'    => $a->updated_at?->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority'   => '0.7',
                    ]);
                }
            });

        // Collections
        Collection::query()->where('is_public', true)
            ->select('slug', 'updated_at')
            ->each(function ($c) use ($urls) {
                $urls->push([
                    'loc'        => route('collections.show', $c->slug),
                    'lastmod'    => $c->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                ]);
            });

        // Exhibitions
        Exhibition::query()->where('is_published', true)
            ->select('slug', 'updated_at')
            ->each(function ($e) use ($urls) {
                $urls->push([
                    'loc'        => route('exhibitions.show', $e->slug),
                    'lastmod'    => $e->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority'   => '0.6',
                ]);
            });

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
