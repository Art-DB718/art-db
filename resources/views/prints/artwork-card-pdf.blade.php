<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $artwork->title }}</title>
    <style>
        /*
         * Gallery-style artwork card. Layout:
         *   ┌────────────────────────────────────────────┐
         *   │                    LOGO                    │  ← header centred
         *   │                                            │
         *   │             ┌──────────────┐               │
         *   │             │   ARTWORK    │  ← dominant   │
         *   │             └──────────────┘               │
         *   │                                            │
         *   │              ARTIST NAME                   │
         *   │              Title, Year                   │
         *   │              medium / edition              │
         *   │              dimensions                    │
         *   │              price                         │
         *   └────────────────────────────────────────────┘
         *
         * DomPDF caveats: no flex, no object-fit, no CSS grid — everything
         * below is plain block/table/inline layout that DomPDF actually
         * renders correctly. The image is scaled with max-width + max-height
         * so it fills as much of the page as possible while preserving its
         * real aspect ratio (never stretched, never cropped).
         */
        @page { margin: 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10.5pt;
            line-height: 1.55;
        }

        /* Header: logo (image or wordmark) centred at the top. Sized to
           match the cert so the two prints read as a set. */
        .header {
            width: 100%;
            margin-bottom: 18pt;
            text-align: center;
            color: #374151;
        }
        .header img { max-height: 80pt; max-width: 280pt; }
        .header .wordmark {
            font-family: DejaVu Serif, serif;
            font-size: 22pt;
            letter-spacing: 0.35em;
            color: #374151;
            text-transform: uppercase;
        }

        /* Big centered artwork; capped by max-height so the meta block
           below always has room even for portrait pieces. */
        .photo-wrap { width: 100%; text-align: center; margin-bottom: 22pt; }
        .photo      { max-width: 100%; max-height: 560pt; }

        /* Metadata block — left-aligned under the photo, spans the full
           A4 content width (no artificial inner-column shrinking) so the
           text sits inside the standard 2 cm page margin. */
        .meta {
            color: #1f2937;
            text-align: left;
        }
        .meta .artist  { font-size: 10.5pt; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4pt; }
        .meta .title   { font-style: italic; font-size: 11pt; margin-bottom: 8pt; }
        .meta .line    { margin-top: 3pt; color: #374151; }
        .meta .price   { margin-top: 10pt; font-weight: bold; color: #1f2937; }

        /* Optional secondary blocks (description / provenance) match the
           left-aligned meta so the whole text stack reads as one block. */
        .aux {
            margin-top: 22pt;
            color: #4b5563;
            font-size: 9.5pt;
            line-height: 1.6;
        }
        .aux .aux-label { text-transform: uppercase; letter-spacing: 0.12em; font-size: 8pt; color: #6b7280; margin-bottom: 4pt; font-weight: bold; }

        /* Footer pinned to the bottom of every A4 page. Kept inside the
           printable band (>= 1 cm from paper edge) so nothing gets
           clipped by the printer's own margin. */
        .footer { position: fixed; bottom: 1.5cm; left: 2cm; right: 2cm; font-size: 9pt; color: #6b7280; text-align: center; }
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
