<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label — {{ $artwork->title }}</title>
    <style>
        @page { margin: 0.3cm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; padding: 0.3cm; font-size: 8pt; line-height: 1.3; }
        .artist { font-weight: bold; font-size: 9pt; margin-bottom: 1pt; }
        .title { font-style: italic; margin-bottom: 4pt; font-size: 8pt; }
        .specs { font-size: 7pt; color: #4b5563; margin-bottom: 3pt; }
        .specs span { display: inline-block; }
        .specs span + span:before { content: ' · '; }
        .inventory { font-size: 6pt; color: #9ca3af; font-family: DejaVu Sans Mono, monospace; }
        .price { font-weight: bold; font-size: 9pt; margin-top: 2pt; }
        .gallery { font-size: 6pt; color: #9ca3af; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 3pt; }
    </style>
</head>
<body>
    @php
        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
    @endphp

    @if ($settings->label_show_logo && $settings->company_name)
        <div class="gallery">{{ $settings->company_name }}</div>
    @endif

    <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
    <div class="title">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</div>

    <div class="specs">
        @if ($artwork->medium?->name)<span>{{ $artwork->medium->name }}</span>@endif
        @if ($settings->label_show_dimensions && $dims)<span>{{ $dims }} cm</span>@endif
    </div>

    @if ($settings->label_show_price)
        @if ($artwork->price && ! $artwork->price_on_request)
            <div class="price">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</div>
        @elseif ($artwork->price_on_request)
            <div class="price" style="font-size:7pt;color:#6b7280;font-weight:normal;">Price on request</div>
        @endif
    @endif

</body>
</html>
