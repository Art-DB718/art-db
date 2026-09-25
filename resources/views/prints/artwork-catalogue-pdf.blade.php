<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artwork catalogue</title>
    <style>
        /*
         * Bulk artwork catalogue — one gallery-style card per artwork,
         * matching the single-card and certificate layouts. First page is
         * a cover with the gallery logo + catalogue title + work count;
         * each subsequent page is a standalone card.
         * Options (show price / description / provenance / sort) come in
         * as $options from the bulk-action form.
         */
        @page { size: A4; margin: 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5pt; line-height: 1.55; }

        /* Header on each artwork page: centred logo dominant. */
        .header { width: 100%; margin-bottom: 24pt; text-align: center; color: #374151; }
        .header img { max-height: 100pt; max-width: 320pt; }
        .header .wordmark {
            font-family: DejaVu Serif, serif; font-size: 26pt; letter-spacing: 0.35em;
            color: #374151; text-transform: uppercase;
        }

        /* Stage: photo + meta share the same 80% centred column. */
        .stage      { width: 80%; margin: 0 auto; }
        .photo-wrap { text-align: left; margin-bottom: 20pt; }
        .photo      { max-width: 100%; max-height: 480pt; }

        .meta       { color: #1f2937; text-align: left; }
        .meta .artist  { font-size: 10.5pt; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4pt; }
        .meta .title   { font-style: italic; font-size: 11pt; margin-bottom: 8pt; }
        .meta .line    { margin-top: 3pt; color: #374151; }
        .meta .price   { margin-top: 10pt; font-weight: bold; color: #1f2937; }

        .aux { margin-top: 18pt; color: #4b5563; font-size: 9.5pt; line-height: 1.6; }
        .aux .aux-label { text-transform: uppercase; letter-spacing: 0.12em; font-size: 8pt; color: #6b7280; margin-bottom: 3pt; font-weight: bold; }

        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }

        /* Cover page. */
        .cover { text-align: center; padding-top: 180pt; page-break-after: always; }
        .cover .brand   { font-family: DejaVu Serif, serif; font-size: 26pt; letter-spacing: 0.35em; color: #374151; text-transform: uppercase; margin-bottom: 40pt; }
        .cover .brand img { max-height: 120pt; max-width: 360pt; }
        .cover .title   { font-family: DejaVu Serif, serif; font-size: 34pt; margin: 0 0 16pt; letter-spacing: 0.08em; color: #1f2937; }
        .cover .sub     { font-size: 10pt; color: #6b7280; letter-spacing: 0.2em; text-transform: uppercase; }
        .cover .count   { margin-top: 60pt; font-size: 11pt; color: #4b5563; }

        /* Footer pinned to every page bottom. */
        .footer { position: fixed; bottom: 1.5cm; left: 2cm; right: 2cm; font-size: 9pt; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @php
        // Defaults so template still works when called without --options.
        $opts = array_merge([
            'show_price'       => (bool) ($settings->card_show_price ?? true),
            'show_description' => true,
            'show_provenance'  => (bool) ($settings->card_show_provenance ?? false),
            'include_cover'    => true,
        ], (array) ($options ?? []));

        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);
    @endphp

    {{-- COVER PAGE --}}
    @if ($opts['include_cover'])
    <div class="cover">
        <div class="brand">
            @if ($settings->logo_path)
                <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
            @else
                {{ $settings->company_name ?? config('app.name') }}
            @endif
        </div>
        <div class="title">Artwork Catalogue</div>
        <div class="sub">{{ now()->format('d.m.Y') }}</div>
        <div class="count">{{ count($artworks) }} {{ count($artworks) === 1 ? 'work' : 'works' }}</div>
    </div>
    @endif

    {{-- ARTWORK CARDS --}}
    @foreach ($artworks as $artwork)
        @php
            $year = $artwork->year_created;
            if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
                $year = $artwork->year_created.'–'.$artwork->year_created_end;
            }
            $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
            $edition = $artwork->edition_number && $artwork->edition_total
                ? $artwork->edition_number.'/'.$artwork->edition_total
                : ($artwork->edition_notes ?: null);
            $techniqueParts = array_filter([$artwork->materials ?: $artwork->medium?->name, $edition]);
            $technique = implode(' ', $techniqueParts);
        @endphp

        <div class="card">
            <div class="header">
                @if ($settings->logo_path)
                    <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
                @elseif ($settings->company_name)
                    <div class="wordmark">{{ $settings->company_name }}</div>
                @endif
            </div>

            <div class="stage">
                @if ($artwork->primary_image)
                    <div class="photo-wrap">
                        <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
                    </div>
                @endif

                <div class="meta">
                    <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                    <div class="title"><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
                    @if ($technique)<div class="line">{{ $technique }}</div>@endif
                    @if ($dims)<div class="line">{{ $dims }} cm</div>@endif
                    @if ($opts['show_price'])
                        @if ($artwork->price && ! $artwork->price_on_request)
                            <div class="line price">{{ $artwork->currency }} {{ number_format((float) $artwork->price, 0, '.', ' ') }}</div>
                        @elseif ($artwork->price_on_request)
                            <div class="line price">Price on request</div>
                        @endif
                    @endif
                    @if ($artwork->is_signed && $artwork->signature_description)
                        <div class="line">Signed — {{ $artwork->signature_description }}</div>
                    @endif
                </div>

                @if ($opts['show_description'] && $artwork->description)
                    <div class="aux">
                        <div class="aux-label">About</div>
                        {{ $artwork->description }}
                    </div>
                @endif
                @if ($opts['show_provenance'] && $artwork->provenance)
                    <div class="aux">
                        <div class="aux-label">Provenance</div>
                        {{ $artwork->provenance }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @if ($footerLine)
        <div class="footer">{{ $footerLine }}</div>
    @endif
</body>
</html>
