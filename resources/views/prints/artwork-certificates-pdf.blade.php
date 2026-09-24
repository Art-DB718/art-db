<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificates of Authenticity</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: DejaVu Serif, serif; color: #1f2937; margin: 0; font-size: 11pt; line-height: 1.55; }
        .cert { page-break-after: always; position: relative; min-height: 24cm; }
        .cert:last-child { page-break-after: auto; }
        .top { text-align: center; margin-bottom: 30pt; }
        .top .gallery { font-size: 11pt; letter-spacing: 0.3em; text-transform: uppercase; color: #6b7280; }
        .top h1 { font-size: 22pt; font-weight: normal; margin: 10pt 0 4pt; letter-spacing: 0.18em; }
        .top .sub { font-size: 9pt; color: #6b7280; letter-spacing: 0.2em; text-transform: uppercase; }
        .body { margin: 30pt 0; }
        /* Two-column layout: photo LEFT, artwork description RIGHT. */
        .layout { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 14pt; }
        .layout td { vertical-align: top; padding: 0; }
        .layout td.photo-col { width: 46%; padding-right: 22pt; }
        .layout td.text-col  { width: 54%; }
        .photo { max-width: 100%; max-height: 260pt; border: 1pt solid #e5e7eb; }
        .artwork-desc { font-size: 10pt; line-height: 1.55; color: #374151; }
        .artwork-desc p { margin: 0 0 8pt; }
        .specs { width: 100%; border-collapse: collapse; margin-top: 20pt; }
        .specs td { padding: 6pt 0; border-bottom: 1pt solid #e5e7eb; vertical-align: top; }
        .specs td.label { width: 35%; color: #6b7280; padding-right: 12pt; }
        .specs td.val { font-weight: bold; }
        .signature { margin-top: 50pt; }
        .signature .line { border-top: 1pt solid #1f2937; width: 240pt; margin-bottom: 4pt; }
        .signature .label { font-size: 9pt; color: #6b7280; letter-spacing: 0.18em; text-transform: uppercase; }
        .cert-footer { font-size: 8pt; color: #9ca3af; text-align: center; margin-top: 30pt; padding-top: 8pt; border-top: 1pt dashed #d1d5db; }
    </style>
</head>
<body>

    @foreach ($artworks as $artwork)
        @php
            $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
            $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
        @endphp

        <div class="cert">
            <div class="top">
                <div class="gallery">{{ $settings->company_name ?? config('app.name') }}</div>
                <h1>Certificate of Authenticity</h1>
                <div class="sub">{{ now()->format('d.m.Y') }}</div>
            </div>

            <div class="body">
                @if ($settings->cert_intro)
                    <div>{!! \App\Support\PrintHtml::render($settings->cert_intro) !!}</div>
                @else
                    <div>This certificate confirms the authenticity of the following original artwork:</div>
                @endif

                <table class="layout">
                    <tr>
                        <td class="photo-col">
                            @if ($artwork->primary_image)
                                <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
                            @endif
                        </td>
                        <td class="text-col">
                            @if ($artwork->description)
                                <div class="artwork-desc">{!! \App\Support\PrintHtml::render($artwork->description) !!}</div>
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="specs">
                    <tr><td class="label">Artist</td><td class="val">{{ $artwork->artist?->display_name ?? '—' }}</td></tr>
                    <tr><td class="label">Title</td><td class="val">{{ $artwork->title }}</td></tr>
                    @if ($artwork->year_created)
                        <tr><td class="label">Year</td><td class="val">{{ $artwork->year_created }}@if ($artwork->year_created_end) – {{ $artwork->year_created_end }}@endif</td></tr>
                    @endif
                    @if ($artwork->medium?->name)
                        <tr><td class="label">Medium</td><td class="val">{{ $artwork->medium->name }}</td></tr>
                    @endif
                    @if ($dims)
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

            <div class="cert-footer">
                {{ $settings->company_name ?? config('app.name') }}
                @if ($settings->business_id) · IČO {{ $settings->business_id }}@endif
                @if ($settings->email) · {{ $settings->email }}@endif
            </div>
        </div>
    @endforeach

</body>
</html>
