<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artwork catalogue</title>
    <style>
        /*
         * Bulk artwork catalogue.
         * - per_page = 1 → one full-page card (matches single-card layout).
         * - per_page > 1 → grid of mini-cards, one header + one footer per
         *   page (position:fixed so DomPDF paints them on every page).
         */
        @page { size: A4; margin: 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5pt; line-height: 1.55; }

        /* Page header — centred logo. */
        .page-header {
            width: 100%; text-align: center; color: #374151;
        }
        .page-header img { max-height: 90pt; max-width: 300pt; }
        .page-header .wordmark {
            font-family: DejaVu Serif, serif; font-size: 22pt; letter-spacing: 0.35em;
            color: #374151; text-transform: uppercase;
        }

        /* Footer pinned to every page bottom via DomPDF fixed. */
        .footer { position: fixed; bottom: 1.5cm; left: 2cm; right: 2cm; font-size: 9pt; color: #6b7280; text-align: center; }

        /*
         * === Single-per-page (per_page = 1) ===
         */
        .single-header { margin-bottom: 24pt; }
        .single-header-block { text-align: center; }
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

        /*
         * === Grid layout (per_page > 1) ===
         * Header + footer are fixed. Content area sits between them
         * with the same padding on each grid page.
         */
        .fixed-top {
            position: fixed;
            top: 0.4cm;
            left: 2cm;
            right: 2cm;
            text-align: center;
        }
        .fixed-top img { max-height: 55pt; max-width: 220pt; }
        .fixed-top .wordmark {
            font-family: DejaVu Serif, serif; font-size: 15pt; letter-spacing: 0.35em;
            color: #374151; text-transform: uppercase;
        }
        .grid-page {
            margin-top: 55pt;      /* reserve space for fixed header */
            page-break-after: always;
        }
        .grid-page:last-child { page-break-after: auto; }

        .grid   { width: 100%; border-collapse: separate; border-spacing: 6pt; }
        .grid td { vertical-align: top; padding: 6pt; }

        .mini-card { text-align: left; }
        .mini-card .mini-photo-wrap { text-align: center; margin-bottom: 6pt; }
        .mini-card .mini-photo      { max-width: 100%; }
        .mini-card .mini-artist { font-size: 8pt; font-weight: bold; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 4pt; }
        .mini-card .mini-title  { font-size: 8pt; font-style: italic; margin-top: 1pt; }
        .mini-card .mini-line   { font-size: 7.5pt; color: #6b7280; margin-top: 1pt; }
        .mini-card .mini-price  { font-size: 8pt; font-weight: bold; margin-top: 3pt; }

        /* Cover page. */
        .cover { text-align: center; padding-top: 180pt; page-break-after: always; }
        .cover .brand   { font-family: DejaVu Serif, serif; font-size: 26pt; letter-spacing: 0.35em; color: #374151; text-transform: uppercase; margin-bottom: 40pt; }
        .cover .brand img { max-height: 120pt; max-width: 360pt; }
        .cover .title   { font-family: DejaVu Serif, serif; font-size: 34pt; margin: 0 0 16pt; letter-spacing: 0.08em; color: #1f2937; }
        .cover .sub     { font-size: 10pt; color: #6b7280; letter-spacing: 0.2em; text-transform: uppercase; }
        .cover .count   { margin-top: 60pt; font-size: 11pt; color: #4b5563; }
    </style>
</head>
<body>
    @php
        $opts = array_merge([
            'show_price'       => (bool) ($settings->card_show_price ?? true),
            'show_description' => true,
            'show_provenance'  => (bool) ($settings->card_show_provenance ?? false),
            'include_cover'    => true,
            'per_page'         => 1,
        ], (array) ($options ?? []));

        $perPage = (int) $opts['per_page'];
        if (! in_array($perPage, [1, 2, 4, 6, 8, 9, 10, 12], true)) {
            $perPage = 1;
        }
        // How many columns the grid uses per per_page value.
        $colsByPer = [1 => 1, 2 => 1, 4 => 2, 6 => 2, 8 => 2, 9 => 3, 10 => 2, 12 => 3];
        $cols = $colsByPer[$perPage] ?? 1;
        // Mini-card photo cap tuned to fit N cards into the printable band
        // (A4 minus 2cm × 2 margins minus fixed-header 55pt minus footer 40pt
        // ≈ 610pt tall / cols wide).
        $miniPhotoCap = match ($perPage) {
            2  => 260,
            4  => 220,
            6  => 150,
            8  => 110,
            9  => 130,
            10 => 90,
            12 => 100,
            default => 480,
        };

        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);

        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
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

    @if ($perPage === 1)
        {{-- === ONE-PER-PAGE LAYOUT — same as single card === --}}
        @foreach ($artworks as $artwork)
            @php
                $year = $artwork->year_created;
                if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
                    $year = $artwork->year_created.'–'.$artwork->year_created_end;
                }
                $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
                $edition = $artwork->edition_number && $artwork->edition_total
                    ? $artwork->edition_number.'/'.$artwork->edition_total
                    : ($artwork->edition_notes ?: null);
                $techniqueParts = array_filter([$artwork->materials ?: $artwork->medium?->name, $edition]);
                $technique = implode(' ', $techniqueParts);
            @endphp
            <div style="page-break-after: {{ $loop->last ? 'auto' : 'always' }};">
                <div class="single-header">
                    <div class="single-header-block">
                        @if ($settings->logo_path)
                            <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="" style="max-height:100pt;max-width:320pt;">
                        @elseif ($settings->company_name)
                            <div class="page-header wordmark" style="font-family: DejaVu Serif, serif; font-size:26pt; letter-spacing:0.35em; color:#374151; text-transform:uppercase;">{{ $settings->company_name }}</div>
                        @endif
                    </div>
                </div>

                <div class="stage">
                    @if ($artwork->primary_image)
                        <div class="photo-wrap"><img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt=""></div>
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
                    </div>
                    @if ($opts['show_description'] && $artwork->description)
                        <div class="aux"><div class="aux-label">About</div>{{ $artwork->description }}</div>
                    @endif
                    @if ($opts['show_provenance'] && $artwork->provenance)
                        <div class="aux"><div class="aux-label">Provenance</div>{{ $artwork->provenance }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        {{-- === GRID LAYOUT — 2/4/6/8/9/10/12 per page === --}}
        {{-- Fixed header on every grid page (DomPDF paints position:fixed
             elements once per output page). --}}
        <div class="fixed-top">
            @if ($settings->logo_path)
                <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
            @elseif ($settings->company_name)
                <div class="wordmark">{{ $settings->company_name }}</div>
            @endif
        </div>

        @foreach (array_chunk($artworks->all(), $perPage) as $chunkIdx => $chunk)
            @php $rows = array_chunk($chunk, $cols); @endphp
            <div class="grid-page">
                <table class="grid">
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($row as $artwork)
                                @php
                                    $year = $artwork->year_created;
                                    if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
                                        $year = $artwork->year_created.'–'.$artwork->year_created_end;
                                    }
                                    $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
                                    $edition = $artwork->edition_number && $artwork->edition_total
                                        ? $artwork->edition_number.'/'.$artwork->edition_total
                                        : ($artwork->edition_notes ?: null);
                                    $techniqueParts = array_filter([$artwork->materials ?: $artwork->medium?->name, $edition]);
                                    $technique = implode(' ', $techniqueParts);
                                @endphp
                                <td style="width: {{ round(100 / $cols, 2) }}%;">
                                    <div class="mini-card">
                                        @if ($artwork->primary_image)
                                            <div class="mini-photo-wrap"><img class="mini-photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="" style="max-height: {{ $miniPhotoCap }}pt;"></div>
                                        @endif
                                        <div class="mini-artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                                        <div class="mini-title"><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
                                        @if ($technique)<div class="mini-line">{{ $technique }}</div>@endif
                                        @if ($dims)<div class="mini-line">{{ $dims }} cm</div>@endif
                                        @if ($opts['show_price'])
                                            @if ($artwork->price && ! $artwork->price_on_request)
                                                <div class="mini-price">{{ $artwork->currency }} {{ number_format((float) $artwork->price, 0, '.', ' ') }}</div>
                                            @elseif ($artwork->price_on_request)
                                                <div class="mini-price">Price on request</div>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            {{-- Pad the last row so DomPDF doesn't stretch the final card. --}}
                            @for ($i = count($row); $i < $cols; $i++)
                                <td style="width: {{ round(100 / $cols, 2) }}%;"></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @endif

    @if ($footerLine)
        <div class="footer">{{ $footerLine }}</div>
    @endif
</body>
</html>
