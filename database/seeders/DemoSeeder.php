<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\ArtworkStatus;
use App\Models\Collection;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Country;
use App\Models\Exhibition;
use App\Models\Genre;
use App\Models\Location;
use App\Models\Medium;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedMediums();
        $this->seedGenres();
        $this->seedStatuses();
        $this->seedContactGroups();
        $this->seedLocations();
        $this->seedArtists();
        $this->seedArtworks();
        $this->seedCollections();
        $this->seedExhibitions();
        $this->seedContacts();
    }

    /* ─────────── Countries ─────────── */
    private function seedCountries(): void
    {
        $list = [
            ['Slovakia', 'SK', 'SVK'], ['Czech Republic', 'CZ', 'CZE'],
            ['Hungary', 'HU', 'HUN'], ['Austria', 'AT', 'AUT'],
            ['Germany', 'DE', 'DEU'], ['Poland', 'PL', 'POL'],
            ['United Kingdom', 'GB', 'GBR'], ['United States', 'US', 'USA'],
            ['France', 'FR', 'FRA'], ['Italy', 'IT', 'ITA'],
            ['Netherlands', 'NL', 'NLD'], ['Switzerland', 'CH', 'CHE'],
        ];
        foreach ($list as [$name, $a2, $a3]) {
            Country::firstOrCreate(['iso_alpha2' => $a2], ['name' => $name, 'iso_alpha3' => $a3]);
        }
    }

    /* ─────────── Mediums (hierarchical) ─────────── */
    private function seedMediums(): void
    {
        $tree = [
            'Painting'     => ['Oil', 'Acrylic', 'Watercolor', 'Tempera', 'Mixed media'],
            'Drawing'      => ['Pencil', 'Charcoal', 'Ink', 'Pastel'],
            'Sculpture'    => ['Bronze', 'Stone', 'Wood', 'Ceramic', 'Mixed materials'],
            'Photography'  => ['Analog', 'Digital', 'Polaroid'],
            'Printmaking'  => ['Lithograph', 'Etching', 'Screen print', 'Woodcut'],
            'Digital'      => ['Digital print', 'NFT', 'Generative'],
            'Installation' => [],
            'Video'        => [],
        ];
        $pos = 0;
        foreach ($tree as $parent => $children) {
            $p = Medium::firstOrCreate(['name' => $parent], ['position' => $pos++]);
            $cp = 0;
            foreach ($children as $child) {
                Medium::firstOrCreate(['name' => $child, 'parent_id' => $p->id], ['position' => $cp++]);
            }
        }
    }

    /* ─────────── Genres ─────────── */
    private function seedGenres(): void
    {
        foreach (['Abstract','Figurative','Portrait','Landscape','Still Life','Conceptual','Pop Art','Minimalism','Surrealism','Realism'] as $g) {
            Genre::firstOrCreate(['name' => $g]);
        }
    }

    /* ─────────── Statuses ─────────── */
    private function seedStatuses(): void
    {
        $list = [
            ['For Sale',   '#10B981', true,  true,  1],
            ['Reserved',   '#F59E0B', true,  false, 2],
            ['Sold',       '#EF4444', true,  false, 3],
            ['Not for Sale', '#6B7280', true, false, 4],
            ['On Loan',    '#3B82F6', false, false, 5],
            ['Archived',   '#9CA3AF', false, false, 6],
        ];
        foreach ($list as [$name, $color, $isPublic, $available, $position]) {
            ArtworkStatus::firstOrCreate(
                ['name' => $name],
                ['color' => $color, 'is_public' => $isPublic, 'counts_as_available' => $available, 'position' => $position]
            );
        }
    }

    /* ─────────── Contact groups ─────────── */
    private function seedContactGroups(): void
    {
        foreach (['VIP Collectors', 'Press', 'Curators', 'Other Galleries', 'Buyers', 'Newsletter'] as $g) {
            ContactGroup::firstOrCreate(['name' => $g]);
        }
    }

    /* ─────────── Locations ─────────── */
    private function seedLocations(): void
    {
        Location::firstOrCreate(['name' => 'Main Storage'], ['type' => 'storage', 'city' => 'Bratislava']);
        Location::firstOrCreate(['name' => 'Gallery Space'], ['type' => 'gallery', 'city' => 'Bratislava']);
        Location::firstOrCreate(['name' => 'Off-site Studio'], ['type' => 'studio', 'city' => 'Nitra']);
    }

    /* ─────────── Artists ─────────── */
    private function seedArtists(): void
    {
        $sk = Country::where('iso_alpha2', 'SK')->first();
        $cz = Country::where('iso_alpha2', 'CZ')->first();
        $de = Country::where('iso_alpha2', 'DE')->first();

        $artists = [
            ['Rudolf', 'Sikora',    1946, null, 'Žilina', $sk?->id, 'Konceptuálny umelec, zakladajúca postava neoavantgardy.'],
            ['Mária',  'Bartuszová',1936, 1996, 'Praha',  $cz?->id, 'Sochárka pracujúca so sadrou a organickými formami.'],
            ['Jozef',  'Jankovič',  1937, 2017, 'Bratislava', $sk?->id, 'Sochár a kresliar, výrazný hlas slovenskej moderny.'],
            ['Anna',   'Daučíková', 1950, null, 'Bratislava', $sk?->id, 'Multimediálna umelkyňa, sklo, video, performance.'],
            ['Hans',   'Reichel',   1972, null, 'Berlin',     $de?->id, 'Contemporary German painter exploring digital abstraction.'],
        ];

        foreach ($artists as [$first, $last, $birth, $death, $birthPlace, $countryId, $shortBio]) {
            Artist::firstOrCreate(
                ['last_name' => $last, 'first_name' => $first],
                [
                    'birth_year'   => $birth,
                    'death_year'   => $death,
                    'birth_place'  => $birthPlace,
                    'country_id'   => $countryId,
                    'short_bio'    => $shortBio,
                    'is_published' => true,
                    'is_featured'  => true,
                ]
            );
        }
    }

    /* ─────────── Artworks ─────────── */
    private function seedArtworks(): void
    {
        $artists = Artist::all()->keyBy(fn ($a) => $a->last_name);
        $mediums = Medium::all()->keyBy('name');
        $genres  = Genre::all()->keyBy('name');
        $forSale = ArtworkStatus::where('name', 'For Sale')->first();
        $reserved= ArtworkStatus::where('name', 'Reserved')->first();
        $sold    = ArtworkStatus::where('name', 'Sold')->first();

        $artworks = [
            // [Artist, title, year, medium, genre, status, h, w, price, signed]
            ['Sikora',    'Topology of the Universe I', 1978, 'Mixed media',    'Conceptual',  $forSale,  100, 80,  4500, true],
            ['Sikora',    'Topology of the Universe II',1979, 'Mixed media',    'Conceptual',  $forSale,  100, 80,  4500, true],
            ['Sikora',    'White Hole',                 1985, 'Acrylic',        'Abstract',    $sold,     120, 90, 12000, true],
            ['Bartuszová','Folded Form III',            1985, 'Bronze',         'Abstract',    $forSale,   45, 30, 18000, false],
            ['Bartuszová','Egg Suite',                  1988, 'Stone',          'Abstract',    $reserved,  60, 50, 22000, false],
            ['Bartuszová','Soft Geometry',              1990, 'Mixed materials','Minimalism',  $forSale,   35, 40,  9500, false],
            ['Jankovič',  'Falling Figure',             1968, 'Bronze',         'Figurative',  $sold,      80, 60, 28000, true],
            ['Jankovič',  'Untitled (Heads)',           1972, 'Pencil',         'Figurative',  $forSale,   42, 30,  2200, true],
            ['Jankovič',  'Group',                      1975, 'Charcoal',       'Figurative',  $forSale,   70, 50,  3400, true],
            ['Daučíková', 'Glass Object I',             1995, 'Mixed materials','Conceptual',  $forSale,   25, 25,  3800, false],
            ['Daučíková', 'Portrait Series',            2001, 'Digital',        'Portrait',    $reserved, 100, 70,  4200, false],
            ['Daučíková', 'Performance Documentation',  2008, 'Digital',        'Conceptual',  $forSale,   60, 40,  2900, false],
            ['Reichel',   'Field 01',                   2022, 'Acrylic',        'Abstract',    $forSale,  140,110,  6800, true],
            ['Reichel',   'Field 02',                   2022, 'Acrylic',        'Abstract',    $forSale,  140,110,  6800, true],
            ['Reichel',   'Field 03',                   2023, 'Oil',            'Abstract',    $forSale,  160,120,  8400, true],
        ];

        foreach ($artworks as [$artistKey, $title, $year, $mediumName, $genreName, $status, $h, $w, $price, $signed]) {
            $artist = $artists[$artistKey] ?? null;
            if (!$artist) continue;

            Artwork::firstOrCreate(
                ['title' => $title, 'artist_id' => $artist->id],
                [
                    'year_created'  => $year,
                    'medium_id'     => $mediums[$mediumName]->id ?? null,
                    'genre_id'      => $genres[$genreName]->id ?? null,
                    'status_id'     => $status?->id,
                    'height_cm'     => $h,
                    'width_cm'      => $w,
                    'price'         => $price,
                    'currency'      => 'EUR',
                    'is_signed'     => $signed,
                    'is_published'  => true,
                    'description'   => 'Ukážkový popis diela pre demo dáta.',
                ]
            );
        }
    }

    /* ─────────── Collections ─────────── */
    private function seedCollections(): void
    {
        $coll = [
            ['Slovak Conceptualists', 'Prierez konceptuálnym umením 70. – 90. rokov.', true],
            ['Sculpture & Object',    'Sochárske diela a 3D objekty zo zbierky.',       true],
            ['Recent Acquisitions',   'Najnovšie pridané diela do zbierky.',            true],
        ];
        foreach ($coll as $i => [$title, $desc, $public]) {
            Collection::firstOrCreate(
                ['title' => $title],
                ['description' => $desc, 'is_public' => $public, 'position' => $i]
            );
        }

        // Pripoj diela ku kolekciám
        $slovak = Collection::where('title', 'Slovak Conceptualists')->first();
        if ($slovak) {
            $ids = Artwork::whereHas('artist', fn ($q) => $q->whereIn('last_name', ['Sikora','Daučíková','Jankovič']))->pluck('id');
            $slovak->artworks()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));
        }

        $sculpture = Collection::where('title', 'Sculpture & Object')->first();
        if ($sculpture) {
            $ids = Artwork::whereIn('medium_id', Medium::whereIn('name', ['Bronze','Stone','Mixed materials'])->pluck('id'))->pluck('id');
            $sculpture->artworks()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));
        }
    }

    /* ─────────── Exhibitions ─────────── */
    private function seedExhibitions(): void
    {
        $loc = Location::where('type', 'gallery')->first();

        Exhibition::firstOrCreate(
            ['title' => 'Topographies of Memory'],
            [
                'type'          => 'group',
                'venue'         => 'Gallery Space, Bratislava',
                'location_id'   => $loc?->id,
                'start_date'    => '2026-09-15',
                'end_date'      => '2026-11-30',
                'curator'       => 'Mária Janotová',
                'description'   => 'Skupinová výstava troch slovenských sochárov a maliarov.',
                'status'        => 'upcoming',
                'is_published'  => true,
            ]
        );

        Exhibition::firstOrCreate(
            ['title' => 'Hans Reichel — Fields'],
            [
                'type'          => 'solo',
                'venue'         => 'Gallery Space, Bratislava',
                'location_id'   => $loc?->id,
                'start_date'    => '2026-06-01',
                'end_date'      => '2026-08-15',
                'curator'       => 'Anton Schottert',
                'description'   => 'Samostatná výstava nemeckého maliara Hansa Reichela.',
                'status'        => 'current',
                'is_published'  => true,
            ]
        );
    }

    /* ─────────── Contacts ─────────── */
    private function seedContacts(): void
    {
        $vip      = ContactGroup::where('name', 'VIP Collectors')->first();
        $press    = ContactGroup::where('name', 'Press')->first();
        $curators = ContactGroup::where('name', 'Curators')->first();
        $sk       = Country::where('iso_alpha2', 'SK')->first();
        $at       = Country::where('iso_alpha2', 'AT')->first();
        $de       = Country::where('iso_alpha2', 'DE')->first();

        $list = [
            ['Peter',    'Novák',    'Private',          'peter.novak@example.sk',   $vip?->id,      $sk?->id],
            ['Eva',      'Horváthová','Horváth Collection','eva@hcollect.eu',         $vip?->id,      $sk?->id],
            ['Thomas',   'Bauer',    'Bauer Art Advisors','thomas@bauerart.at',       $vip?->id,      $at?->id],
            ['Klaus',    'Müller',   'Kunst Forum',      'k.muller@kunstforum.de',    $press?->id,    $de?->id],
            ['Lucia',    'Krajčová', 'SME',              'lucia@sme.sk',              $press?->id,    $sk?->id],
            ['Mária',    'Janotová', 'Slovak National Gallery','m.janotova@sng.sk',   $curators?->id, $sk?->id],
            ['Daniel',   'Grúň',     'Curator (independent)','d.grun@example.com',    $curators?->id, $sk?->id],
            ['Marta',    'Šimková',  'Private',          'marta.simkova@example.sk',  $vip?->id,      $sk?->id],
        ];

        foreach ($list as [$first, $last, $org, $email, $groupId, $countryId]) {
            Contact::firstOrCreate(
                ['email' => $email],
                [
                    'first_name'   => $first,
                    'last_name'    => $last,
                    'organization' => $org,
                    'group_id'     => $groupId,
                    'country_id'   => $countryId,
                    'subscribed_to_newsletter' => true,
                ]
            );
        }
    }
}
