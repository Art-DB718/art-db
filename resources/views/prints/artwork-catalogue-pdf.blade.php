<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artwork catalogue</title>
    <style>
        @page { margin: 1.2cm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; font-size: 10pt; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        .photo { width: 100%; max-height: 340pt; margin-bottom: 14pt; }
        .photo-wrap { text-align: center; margin-bottom: 14pt; }
        .header { border-bottom: 2pt solid #1f2937; padding-bottom: 8pt; margin-bottom: 10pt; }
        .artist { font-size: 11pt; font-weight: bold; color: #374151; margin-bottom: 2pt; }
        h1 { margin: 0; font-size: 16pt; font-style: italic; font-weight: normal; }
        .price { font-size: 13pt; font-weight: bold; float: right; margin-top: -20pt; }
        .specs { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
        .specs td { padding: 3pt 0; vertical-align: top; }
        .specs td.label { color: #6b7280; font-weight: bold; width: 25%; padding-right: 12pt; }
        .description { line-height: 1.5; color: #374151; font-size: 9.5pt; }
        .page-footer { position: running(footer); font-size: 8pt; color: #9ca3af; text-align: center; }
        .footer { margin-top: 14pt; padding-top: 8pt; border-top: 1pt dashed #d1d5db; font-size: 8pt; color: #9ca3af; text-align: center; }
        .cover-title { font-family: DejaVu Serif, serif; font-size: 28pt; margin: 0 0 8pt; letter-spacing: 0.08em; }
        .cover-sub { font-size: 9pt; color: #6b7280; letter-spacing: 0.2em; text-transform: uppercase; }
    </style>
</head>
<body>

    {{-- COVER PAGE --}}
    <div class="card" style="text-align:center; padding-top:140pt;">
        <p class="cover-sub">{{ $settings->company_name ?? config('app.name') }}</p>
        <h1 class="cover-title">Artwork Catalogue</h1>
        <p class="cover-sub" style="margin-top:24pt;">{{ now()->format('d.m.Y') }}</p>
        <p style="margin-top:60pt;font-size:10pt;color:#6b7280;">{{ count($artworks) }} {{ count($artworks) === 1 ? 'work' : 'works' }}</p>
    </div>

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
                ? $artwork->edition_number.' / '.$artwork->edition_total
                : ($artwork->edition_notes ?: null);
        @endphp

        <div class="card">
            @if ($artwork->primary_image)
                <div class="photo-wrap">
                    <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
                </div>
            @endif

            <div class="header">
                @if ($settings->card_show_price)
                    @if ($artwork->price && ! $artwork->price_on_request)
                        <div class="price">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</div>
                    @elseif ($artwork->price_on_request)
                        <div class="price" style="font-size:10pt;color:#6b7280;font-weight:normal;">Price on request</div>
                    @endif
                @endif
                <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                <h1>{{ $artwork->title }}@if ($year), {{ $year }}@endif</h1>
            </div>

            <table class="specs">
                @if ($artwork->medium?->name)<tr><td class="label">Medium</td><td>{{ $artwork->medium->name }}</td></tr>@endif
                @if ($artwork->materials)<tr><td class="label">Materials</td><td>{{ $artwork->materials }}</td></tr>@endif
                @if ($dims)<tr><td class="label">Dimensions</td><td>{{ $dims }} cm</td></tr>@endif
                @if ($edition)<tr><td class="label">Edition</td><td>{{ $edition }}</td></tr>@endif
                @if ($artwork->is_signed)<tr><td class="label">Signed</td><td>Yes{{ $artwork->signature_description ? ' — '.$artwork->signature_description : '' }}</td></tr>@endif
                @if ($artwork->is_framed)<tr><td class="label">Framed</td><td>Yes</td></tr>@endif
            </table>

            @if ($artwork->description)
                <div class="description">{{ $artwork->description }}</div>
            @endif

            @php
                $cardFooter = $settings->card_footer_text
                    ?: ($settings->company_name.($settings->email ? ' · '.$settings->email : ''));
            @endphp
            @if (filled($cardFooter))
                <div class="footer">{{ $cardFooter }}</div>
            @endif
        </div>
    @endforeach

</body>
</html>
