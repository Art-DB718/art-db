<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — {{ $artwork->title }}</title>
    <style>
        /*
         * Browser-viewable Certificate of Authenticity. Mirrors the PDF:
         * large centred logo → title → intro → photo | specs → footer.
         * No signature block, no provenance.
         */
        * { box-sizing: border-box; }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            color: #1f2937;
            margin: 0;
            padding: 2.5rem 1.5rem;
            background: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 3rem 3rem 2rem;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: relative;
            min-height: calc(297mm - 4cm); /* A4 height minus top+bottom @page margin */
        }
        .actions { max-width: 820px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; font-family: Helvetica, Arial, sans-serif; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }

        /* Header — dominant logo/wordmark, centred. */
        .header { text-align: center; margin-bottom: 2rem; }
        .header img { max-height: 100px; max-width: 320px; display: inline-block; }
        .header .wordmark {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.4rem;
            letter-spacing: 0.35em;
            color: #374151;
            text-transform: uppercase;
        }

        .title { text-align: center; font-size: 1.6rem; margin: 1rem 0 1.5rem; color: #1f2937; }
        .intro { text-align: center; color: #374151; margin: 0 auto 3rem; max-width: 500px; line-height: 1.6; }
        .intro p { margin: 0 0 0.4rem; }

        /* Photo | specs block, centred in a 78% inner column with a
           1.5 cm gap below the intro. */
        .artwork-block {
            width: 78%;
            margin: 43px auto 0; /* ~1.5 cm above */
            display: table;
        }
        .artwork-block .row  { display: table-row; }
        .artwork-block .photo-col, .artwork-block .specs-col { display: table-cell; vertical-align: top; }
        .artwork-block .photo-col { width: 46%; padding-right: 24px; }
        .artwork-block .specs-col { width: 54%; padding-top: 8px; }
        .artwork-block img { max-width: 100%; max-height: 300px; height: auto; display: block; }

        .specs { width: 100%; border-collapse: collapse; }
        .specs td { padding: 4px 0; vertical-align: top; color: #1f2937; }
        .specs td.label { color: #4b5563; padding-right: 8px; width: 32%; }
        .specs td.val   { font-weight: 700; }

        .footer {
            position: absolute;
            bottom: 1.5rem;
            left: 3rem;
            right: 3rem;
            font-size: 0.85rem;
            color: #4b5563;
            text-align: center;
        }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                border: none;
                min-height: 0;
                max-width: none;
                width: auto;
                margin: 0;
                position: static;
            }
            /* Footer flows naturally in print — position:absolute inside a
               collapsed sheet floats it off-page. */
            .footer {
                position: static;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid #e5e7eb;
            }
            .actions, .no-print { display: none !important; }
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body>
    @php
        $year = $artwork->year_created;
        if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
            $year = $artwork->year_created.'–'.$artwork->year_created_end;
        }
        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');

        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);
    @endphp

    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="sheet">
        <div class="header">
            @if ($settings->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="">
            @elseif ($settings->company_name)
                <div class="wordmark">{{ $settings->company_name }}</div>
            @endif
        </div>

        <div class="title">Certificate of Authenticity</div>

        <div class="intro">
            @if (filled($settings->cert_intro))
                {!! \App\Support\PrintHtml::render($settings->cert_intro) !!}
            @else
                <p>{{ $settings->company_name ?? config('app.name') }} confirms the authenticity of the artwork described below.</p>
            @endif
        </div>

        <div class="artwork-block">
            <div class="row">
                <div class="photo-col">
                    @if ($artwork->primary_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}" alt="">
                    @endif
                </div>
                <div class="specs-col">
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
                </div>
            </div>
        </div>

        @if ($footerLine)
            <div class="footer">{{ $footerLine }}</div>
        @endif
    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
