<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $artwork->title }}</title>
    <style>
        /*
         * Browser-viewable artwork card. Mirrors the PDF layout so both
         * prints read as one gallery-branded set: large centred logo,
         * dominant photo, meta block in a 78% centred column with a
         * 1.5 cm gap under the image.
         */
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 2.5rem 1.5rem;
            background: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 2.5rem 3rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .actions { max-width: 820px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }

        /* Header — logo / wordmark centred, dominant on the page. */
        .header { text-align: center; margin-bottom: 1.2rem; color: #374151; }
        .header img { max-height: 80px; max-width: 280px; display: inline-block; }
        .header .wordmark {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.4rem;
            letter-spacing: 0.35em;
            color: #374151;
            text-transform: uppercase;
        }

        /* Dominant artwork. */
        .photo-wrap { text-align: center; margin-bottom: 22px; }
        .photo      { max-width: 100%; max-height: 560px; height: auto; display: inline-block; }

        /* Meta block — text centred so it aligns with the centred photo. */
        .meta { color: #1f2937; text-align: center; }
        .meta .artist  { font-size: 0.9rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.25rem; }
        .meta .title   { font-style: italic; font-size: 1rem; margin-bottom: 0.4rem; }
        .meta .line    { margin-top: 0.15rem; color: #374151; }
        .meta .price   { margin-top: 0.6rem; font-weight: 700; color: #1f2937; }

        /* Optional secondary blocks (About / Provenance). */
        .aux { width: 78%; margin: 1.2rem auto 0; color: #4b5563; font-size: 0.9rem; line-height: 1.6; text-align: center; }
        .aux .aux-label { text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.7rem; color: #6b7280; margin-bottom: 0.25rem; font-weight: 700; }

        .gallery-meta { margin-top: 2rem; padding-top: 1rem; font-size: 0.8rem; color: #9ca3af; text-align: center; }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; padding: 1.5rem; }
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
        $dims = collect([
            $fmt($artwork->height_cm),
            $fmt($artwork->width_cm),
            $fmt($artwork->depth_cm),
        ])->filter()->implode(' × ');
        $edition = $artwork->edition_number && $artwork->edition_total
            ? $artwork->edition_number.'/'.$artwork->edition_total
            : ($artwork->edition_notes ?: null);
        $techniqueParts = array_filter([$artwork->materials ?: $artwork->medium?->name, $edition]);
        $technique = implode(' ', $techniqueParts);
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

        @if ($artwork->primary_image)
            <div class="photo-wrap">
                <img class="photo" src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}" alt="{{ $artwork->title }}">
            </div>
        @endif

        <div class="meta">
            <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
            <div class="title"><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
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
            <div class="gallery-meta">{{ $cardFooter }}</div>
        @endif
    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
