<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Collection;
use App\Models\Exhibition;
use App\Models\Gallery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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
            ->select('slug', 'name', 'logo', 'cover_image', 'updated_at')
            ->each(function ($g) use ($urls) {
                $urls->push([
                    'loc'         => route('galleries.show', $g->slug),
                    'lastmod'     => $g->updated_at?->toAtomString(),
                    'changefreq'  => 'monthly',
                    'priority'    => '0.7',
                    'image'       => $g->cover_image ? Storage::url($g->cover_image)
                                    : ($g->logo ? Storage::url($g->logo) : null),
                    'image_title' => $g->name,
                ]);
            });

        // Artworks
        Artwork::query()->where('is_published', true)
            ->select('slug', 'title', 'primary_image', 'updated_at')
            ->chunk(500, function ($chunk) use ($urls) {
                foreach ($chunk as $a) {
                    $urls->push([
                        'loc'         => route('artworks.show', $a->slug),
                        'lastmod'     => $a->updated_at?->toAtomString(),
                        'changefreq'  => 'monthly',
                        'priority'    => '0.7',
                        'image'       => $a->primary_image ? Storage::url($a->primary_image) : null,
                        'image_title' => $a->title,
                    ]);
                }
            });

        // Artists
        Artist::query()->where('is_published', true)
            ->select('slug', 'first_name', 'last_name', 'profile_image', 'cover_image', 'updated_at')
            ->chunk(500, function ($chunk) use ($urls) {
                foreach ($chunk as $a) {
                    $urls->push([
                        'loc'         => route('artists.show', $a->slug),
                        'lastmod'     => $a->updated_at?->toAtomString(),
                        'changefreq'  => 'monthly',
                        'priority'    => '0.7',
                        'image'       => $a->profile_image ? Storage::url($a->profile_image)
                                        : ($a->cover_image ? Storage::url($a->cover_image) : null),
                        'image_title' => trim($a->first_name.' '.$a->last_name),
                    ]);
                }
            });

        // Collections
        Collection::query()->where('is_public', true)
            ->select('slug', 'title', 'cover_image', 'updated_at')
            ->each(function ($c) use ($urls) {
                $urls->push([
                    'loc'         => route('collections.show', $c->slug),
                    'lastmod'     => $c->updated_at?->toAtomString(),
                    'changefreq'  => 'monthly',
                    'priority'    => '0.6',
                    'image'       => $c->cover_image ? Storage::url($c->cover_image) : null,
                    'image_title' => $c->title,
                ]);
            });

        // Exhibitions
        Exhibition::query()->where('is_published', true)
            ->select('slug', 'title', 'poster_image', 'updated_at')
            ->each(function ($e) use ($urls) {
                $urls->push([
                    'loc'         => route('exhibitions.show', $e->slug),
                    'lastmod'     => $e->updated_at?->toAtomString(),
                    'changefreq'  => 'monthly',
                    'priority'    => '0.6',
                    'image'       => $e->poster_image ? Storage::url($e->poster_image) : null,
                    'image_title' => $e->title,
                ]);
            });

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
