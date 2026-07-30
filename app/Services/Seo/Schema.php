<?php

namespace App\Services\Seo;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Exhibition;
use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;

/**
 * Build schema.org JSON-LD objects for public pages.
 *
 * All builders return a plain array — cast to json at render time in the
 * layout. Return null when a required field is missing so callers can skip
 * rendering the <script> tag cleanly.
 */
class Schema
{
    /** Root Organization + WebSite blob (rendered on home + as fallback). */
    public static function organization(): array
    {
        $name = config('app.name', 'Art DB');
        $url  = url('/');

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'WebSite',
                    '@id'   => $url.'#website',
                    'url'   => $url,
                    'name'  => $name,
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $url.'artworks?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type' => 'Organization',
                    '@id'   => $url.'#organization',
                    'url'   => $url,
                    'name'  => $name,
                    'description' => 'Online archive and platform for galleries, artists and collectors — browse contemporary art, follow artists, curate collections.',
                ],
            ],
        ];
    }

    public static function artwork(Artwork $artwork): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'VisualArtwork',
            '@id'      => route('artworks.show', $artwork).'#artwork',
            'name'     => $artwork->title,
            'url'      => route('artworks.show', $artwork),
        ];

        if ($artwork->artist) {
            $data['creator'] = [
                '@type' => 'Person',
                'name'  => $artwork->artist->display_name,
                'url'   => route('artists.show', $artwork->artist),
            ];
        }
        if ($artwork->year_created) {
            $data['dateCreated'] = (string) $artwork->year_created;
        }
        if ($artwork->medium?->name) {
            $data['artMedium'] = $artwork->medium->name;
        }
        if ($artwork->genre?->name) {
            $data['artform'] = $artwork->genre->name;
        }
        if ($artwork->description) {
            $data['description'] = strip_tags($artwork->description);
        }
        if ($artwork->height_cm && $artwork->width_cm) {
            $data['width']  = ['@type' => 'QuantitativeValue', 'value' => $artwork->width_cm,  'unitCode' => 'CMT'];
            $data['height'] = ['@type' => 'QuantitativeValue', 'value' => $artwork->height_cm, 'unitCode' => 'CMT'];
        }
        if ($artwork->primary_image) {
            $data['image'] = Storage::url($artwork->primary_image);
        }
        if (! $artwork->price_on_request && $artwork->price) {
            $data['offers'] = [
                '@type'         => 'Offer',
                'price'         => (string) $artwork->price,
                'priceCurrency' => $artwork->currency ?: 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'url'           => route('artworks.show', $artwork),
            ];
        }

        return $data;
    }

    public static function artist(Artist $artist): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            '@id'      => route('artists.show', $artist).'#person',
            'name'     => $artist->display_name,
            'url'      => route('artists.show', $artist),
        ];

        if ($artist->birth_year) {
            $data['birthDate'] = (string) $artist->birth_year;
        }
        if ($artist->birth_place) {
            $data['birthPlace'] = ['@type' => 'Place', 'name' => $artist->birth_place];
        }
        if ($artist->country?->name) {
            $data['nationality'] = ['@type' => 'Country', 'name' => $artist->country->name];
        }
        if ($artist->profile_image) {
            $data['image'] = Storage::url($artist->profile_image);
        }
        if ($artist->short_bio) {
            $data['description'] = strip_tags($artist->short_bio);
        }
        if ($artist->website) {
            $data['sameAs'] = [$artist->website];
        }
        // Art-DB is a "art platform" — mark them as an artist
        $data['jobTitle'] = 'Artist';

        return $data;
    }

    public static function gallery(Gallery $gallery): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'ArtGallery',
            '@id'      => route('galleries.show', $gallery).'#gallery',
            'name'     => $gallery->name,
            'url'      => route('galleries.show', $gallery),
        ];

        if ($gallery->description) {
            $data['description'] = strip_tags($gallery->description);
        }
        if ($gallery->logo) {
            $data['logo'] = Storage::url($gallery->logo);
        }
        if ($gallery->cover_image) {
            $data['image'] = Storage::url($gallery->cover_image);
        }
        if ($gallery->email) {
            $data['email'] = $gallery->email;
        }
        if ($gallery->phone) {
            $data['telephone'] = $gallery->phone;
        }
        if ($gallery->website) {
            $data['sameAs'] = [$gallery->website];
        }
        // Postal address
        $addr = array_filter([
            'streetAddress'   => trim(collect([$gallery->address_line1, $gallery->address_line2])->filter()->implode(', ')),
            'postalCode'      => $gallery->postal_code,
            'addressLocality' => $gallery->city,
            'addressCountry'  => $gallery->country?->name,
        ]);
        if ($addr) {
            $data['address'] = array_merge(['@type' => 'PostalAddress'], $addr);
        }

        return $data;
    }

    public static function exhibition(Exhibition $exhibition): array
    {
        $data = [
            '@context'  => 'https://schema.org',
            '@type'     => 'ExhibitionEvent',
            '@id'       => route('exhibitions.show', $exhibition).'#event',
            'name'      => $exhibition->title,
            'url'       => route('exhibitions.show', $exhibition),
        ];

        if ($exhibition->description) {
            $data['description'] = strip_tags($exhibition->description);
        }
        if ($exhibition->start_date) {
            $data['startDate'] = $exhibition->start_date->toDateString();
        }
        if ($exhibition->end_date) {
            $data['endDate'] = $exhibition->end_date->toDateString();
        }
        if ($exhibition->poster_image) {
            $data['image'] = Storage::url($exhibition->poster_image);
        }
        if ($exhibition->venue) {
            $data['location'] = ['@type' => 'Place', 'name' => $exhibition->venue];
        }
        // Set default event status (default to scheduled unless we track otherwise)
        $data['eventStatus']    = 'https://schema.org/EventScheduled';
        $data['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

        return $data;
    }

    /**
     * BreadcrumbList — pass an ordered list of [label, url] pairs.
     * Home is prepended automatically; caller passes only the descent from home.
     */
    public static function breadcrumbs(array $trail): array
    {
        array_unshift($trail, ['Home', url('/')]);
        $items = [];
        foreach ($trail as $i => [$label, $u]) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $label,
                'item'     => $u,
            ];
        }
        return [
            '@context'         => 'https://schema.org',
            '@type'            => 'BreadcrumbList',
            'itemListElement'  => $items,
        ];
    }
}
