<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — {{ $artwork->title }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: DejaVu Serif, serif; color: #1f2937; margin: 0; font-size: 11pt; line-height: 1.55; }
        .top { text-align: center; margin-bottom: 36pt; }
        .top .gallery { font-family: DejaVu Serif, serif; font-size: 11pt; letter-spacing: 0.3em; text-transform: uppercase; color: #6b7280; }
        .top h1 { font-size: 24pt; font-weight: normal; margin: 10pt 0 4pt; letter-spacing: 0.18em; }
        .top .sub { font-size: 10pt; color: #6b7280; letter-spacing: 0.2em; text-transform: uppercase; }
        .body { margin: 36pt 0; }
        .intro { font-size: 11pt; }
        .specs { width: 100%; border-collapse: collapse; margin: 24pt 0; }
        .specs td { padding: 6pt 0; border-bottom: 1pt solid #e5e7eb; vertical-align: top; }
        .specs td.label { width: 35%; color: #6b7280; padding-right: 12pt; }
        .specs td.val { font-weight: bold; }
        .signature { margin-top: 60pt; }
        .signature .line { border-top: 1pt solid #1f2937; width: 240pt; margin-bottom: 4pt; }
        .signature .label { font-size: 9pt; color: #6b7280; letter-spacing: 0.18em; text-transform: uppercase; }
        .footer { position: absolute; bottom: 1cm; left: 2cm; right: 2cm; font-size: 8pt; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="top">
        <div class="gallery">{{ $settings->company_name ?? config('app.name') }}</div>
        <h1>Certificate of Authenticity</h1>
        <div class="sub">{{ now()->format('d.m.Y') }}</div>
    </div>

    <div class="body">
        @if ($settings->cert_intro)
            <div class="intro">{!! nl2br(e($settings->cert_intro)) !!}</div>
        @else
            <div class="intro">This certificate confirms the authenticity of the following original artwork:</div>
        @endif

        <table class="specs">
            <tr><td class="label">Artist</td><td class="val">{{ $artwork->artist?->display_name ?? '—' }}</td></tr>
            <tr><td class="label">Title</td><td class="val">{{ $artwork->title }}</td></tr>
            @if ($artwork->year_created)
                <tr><td class="label">Year</td><td class="val">{{ $artwork->year_created }}@if ($artwork->year_created_end) – {{ $artwork->year_created_end }}@endif</td></tr>
            @endif
            @if ($artwork->medium?->name)
                <tr><td class="label">Medium</td><td class="val">{{ $artwork->medium->name }}</td></tr>
            @endif
            @if ($artwork->height_cm || $artwork->width_cm)
                @php
                    $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                    $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
                @endphp
                <tr><td class="label">Dimensions</td><td class="val">{{ $dims }} cm</td></tr>
            @endif
            @if ($artwork->edition_number && $artwork->edition_total)
                <tr><td class="label">Edition</td><td class="val">{{ $artwork->edition_number }} / {{ $artwork->edition_total }}</td></tr>
            @endif
        </table>
    </div>

    <div class="signature">
        <div class="line"></div>
        <div class="label">{{ $settings->cert_signature_label ?: 'For the gallery' }}</div>
    </div>

    <div class="footer">
        {{ $settings->company_name ?? config('app.name') }}
        @if ($settings->business_id) · IČO {{ $settings->business_id }}@endif
        @if ($settings->email) · {{ $settings->email }}@endif
    </div>
</body>
</html>
