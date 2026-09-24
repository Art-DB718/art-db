<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $artwork->title }}</title>
    <style>
        /*
         * Gallery-style artwork card. Layout is deliberately minimalist:
         *   ┌────────────────────────────────────────────┐
         *   │                                       LOGO │  ← header (small)
         *   │                                            │
         *   │             ┌──────────────┐               │
         *   │             │              │               │
         *   │             │   ARTWORK    │  ← dominant   │
         *   │             │  (max ~70%)  │               │
         *   │             │              │               │
         *   │             └──────────────┘               │
         *   │  ARTIST NAME                               │
         *   │  Title, Year                               │
         *   │  medium / edition                          │
         *   │  dimensions                                │
         *   │  price                                     │
         *   └────────────────────────────────────────────┘
         *
         * DomPDF caveats: no flex, no object-fit, no CSS grid — everything
         * below is plain block/table/inline layout that DomPDF actually
         * renders correctly. The image is scaled with max-width + max-height
         * so it fills as much of the page as possible while preserving its
         * real aspect ratio (never stretched, never cropped).
         */
        @page { margin: 1.4cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10pt;
            line-height: 1.4;
        }

        /* Header: logo (image or text) pinned to the top-right corner. */
        .header {
            width: 100%;
            margin-bottom: 24pt;
            text-align: right;
            color: #4b5563;
        }
        .header img { max-height: 28pt; max-width: 120pt; }
        .header .wordmark {
            font-family: DejaVu Serif, serif;
            font-size: 11pt;
            letter-spacing: 0.35em;
            color: #4b5563;
        }

        /* Big centered artwork; capped by max-height so the meta block
           below always has room even for portrait pieces. */
        .photo-wrap { width: 100%; text-align: center; margin-bottom: 32pt; }
        .photo      { max-width: 100%; max-height: 560pt; }

        /* Metadata block, bottom-left. */
        .meta { color: #1f2937; }
        .meta .artist  { font-size: 10pt; font-weight: bold; letter-spacing: 0.06em; text-transform: uppercase; }
        .meta .title   { font-style: italic; }
        .meta .line    { margin-top: 2pt; }
        .meta .price   { margin-top: 6pt; }

        /* Optional secondary blocks (description / provenance) stay small
           and quiet under the meta. Show only when the setting is on. */
        .aux { margin-top: 22pt; color: #4b5563; font-size: 9.5pt; line-height: 1.5; }
        .aux .aux-label { text-transform: uppercase; letter-spacing: 0.1em; font-size: 8pt; color: #6b7280; margin-bottom: 3pt; font-weight: bold; }

        .footer { position: fixed; bottom: 0.4cm; left: 1.4cm; right: 1.4cm; font-size: 8pt; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    @php
        $year = $artwork->year_created;
        if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
            $year = $artwork->year_created.'–'.$artwork->year_created_end;
        }
        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([
            $fmt($artwork->height_cm),
            $fmt($artwork->width_cm),
            $fmt($artwork->depth_cm),
        ])->filter()->implode(' x ');
        $edition = $artwork->edition_number && $artwork->edition_total
            ? $artwork->edition_number.'/'.$artwork->edition_total
            : ($artwork->edition_notes ?: null);

        // Combine medium and edition on the same "technique" line so it
        // reads "serigrafia 21/25" rather than two separate rows.
        $techniqueParts = array_filter([
            $artwork->materials ?: $artwork->medium?->name,
            $edition,
        ]);
        $technique = implode(' ', $techniqueParts);
    @endphp

    {{-- HEADER — logo image if configured, else wordmark from settings.company_name. --}}
    <div class="header">
        @if ($settings->logo_path)
            <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
        @elseif ($settings->company_name)
            <span class="wordmark">{{ $settings->company_name }}</span>
        @endif
    </div>

    {{-- DOMINANT ARTWORK --}}
    @if ($artwork->primary_image)
        <div class="photo-wrap">
            <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
        </div>
    @endif

    {{-- METADATA — minimal, aligned left, mimics the gallery-card sample. --}}
    <div class="meta">
        <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
        <div class="title line">
            <em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif
        </div>
        @if ($technique)<div class="line">{{ $technique }}</div>@endif
        @if ($dims)<div class="line">{{ $dims }} cm</div>@endif
        @if ($settings->card_show_price)
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

    {{-- Optional secondary blocks — kept small so they don't compete with the image. --}}
    @if ($artwork->description)
        <div class="aux">
            <div class="aux-label">About</div>
            {{ $artwork->description }}
        </div>
    @endif
    @if ($settings->card_show_provenance && $artwork->provenance)
        <div class="aux">
            <div class="aux-label">Provenance</div>
            {{ $artwork->provenance }}
        </div>
    @endif

    @php
        $cardFooter = $settings->card_footer_text
            ?: ($settings->company_name.($settings->email ? ' · '.$settings->email : ''));
    @endphp
    @if (filled($cardFooter))
        <div class="footer">{{ $cardFooter }}</div>
    @endif
</body>
</html>
