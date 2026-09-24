<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — {{ $artwork->title }}</title>
    <style>
        /*
         * Certificate of Authenticity, A4.
         * Layout (matches gallery reference):
         *   ┌────────────────────────────────────────┐
         *   │         ROMAN FECIK GALLERY            │  ← wordmark / logo
         *   │                                         │
         *   │        Certificate of Authenticity      │  ← title
         *   │                                         │
         *   │    <intro paragraph, centred>           │
         *   │                                         │
         *   │   (big whitespace)                      │
         *   │                                         │
         *   │   ┌──────────┐    Artist:      Name    │
         *   │   │          │    Title:       …       │
         *   │   │  PHOTO   │    Dimensions:  …       │
         *   │   │          │    Medium:      …       │
         *   │   └──────────┘    Year:        …       │
         *   │                                         │
         *   │   Address, Phone, Email, Web  ← footer  │
         *   └────────────────────────────────────────┘
         */
        @page { size: A4; margin: 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11pt;
            line-height: 1.55;
        }

        /* Header wordmark / logo — centred at top, deliberately larger
           than the 'Certificate of Authenticity' title below it so the
           gallery brand reads first. */
        .top { text-align: center; margin-bottom: 34pt; }
        .top img { max-height: 100pt; max-width: 320pt; }
        .top .wordmark {
            font-family: DejaVu Serif, serif;
            font-size: 22pt;
            letter-spacing: 0.35em;
            color: #374151;
            text-transform: uppercase;
        }

        .title {
            font-family: DejaVu Serif, serif;
            font-size: 16pt;
            text-align: center;
            margin: 18pt 0 26pt;
            color: #1f2937;
        }
        .intro { text-align: center; color: #374151; margin: 0 auto 60pt; max-width: 440pt; }
        .intro p { margin: 0 0 6pt; }

        /* Bottom section: photo LEFT, spec labels RIGHT.
           The table is narrower than the page's content width and pushed
           in from both sides with margin auto, so the whole photo + specs
           block sits centred on the page with a clear ~1.5–2cm cushion
           from each paper edge (paper edge → page margin 2cm → extra
           block inset ≈ 1.5cm on each side). */
        .layout {
            width: 78%;
            /* Push the photo+specs block ~1.5 cm (≈ 43pt) further down
               below the intro paragraph. */
            margin: 103pt auto 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .layout td { vertical-align: top; padding: 0; }
        .layout td.photo-col { width: 46%; padding-right: 24pt; }
        .layout td.specs-col { width: 54%; padding-top: 8pt; }
        .photo { max-width: 100%; max-height: 300pt; }

        .specs { width: 100%; border-collapse: collapse; }
        .specs td { padding: 4pt 0; vertical-align: top; color: #1f2937; }
        .specs td.label { color: #4b5563; padding-right: 8pt; width: 32%; }
        .specs td.val   { font-weight: bold; }

        /* Footer — pinned to page bottom, single centred line. */
        .footer {
            position: fixed;
            bottom: 0.8cm;
            left: 2cm;
            right: 2cm;
            font-size: 9.5pt;
            color: #4b5563;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([
            $fmt($artwork->height_cm),
            $fmt($artwork->width_cm),
            $fmt($artwork->depth_cm),
        ])->filter()->implode(' x ');

        $year = $artwork->year_created;
        if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
            $year = $artwork->year_created.'–'.$artwork->year_created_end;
        }

        // Footer address line — assembled from InvoiceSetting fields.
        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);
    @endphp

    <div class="top">
        @if ($settings->logo_path)
            <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
        @elseif ($settings->company_name)
            <div class="wordmark">{{ $settings->company_name }}</div>
        @endif
    </div>

    <div class="title">Certificate of Authenticity</div>

    <div class="intro">
        @if ($settings->cert_intro)
            {!! \App\Support\PrintHtml::render($settings->cert_intro) !!}
        @else
            <p>{{ $settings->company_name ?? config('app.name') }} confirms the authenticity of the artwork described below.</p>
        @endif
    </div>

    <table class="layout">
        <tr>
            <td class="photo-col">
                @if ($artwork->primary_image)
                    <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
                @endif
            </td>
            <td class="specs-col">
                <table class="specs">
                    <tr><td class="label">Artist</td><td class="val">{{ $artwork->artist?->display_name ?? '—' }}</td></tr>
                    <tr><td class="label">Title</td><td class="val">{{ $artwork->title }}</td></tr>
                    @if ($dims)
                        <tr><td class="label">Dimensions</td><td class="val">{{ $dims }} cm</td></tr>
                    @endif
                    @if ($artwork->medium?->name || $artwork->materials)
                        <tr><td class="label">Medium</td><td class="val">{{ $artwork->materials ?: $artwork->medium?->name }}</td></tr>
                    @endif
                    @if ($year)
                        <tr><td class="label">Year</td><td class="val">{{ $year }}</td></tr>
                    @endif
                    @if ($artwork->edition_number && $artwork->edition_total)
                        <tr><td class="label">Edition</td><td class="val">{{ $artwork->edition_number }} / {{ $artwork->edition_total }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if ($footerLine)
        <div class="footer">{{ $footerLine }}</div>
    @endif
</body>
</html>
