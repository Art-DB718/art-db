<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artwork cards</title>
    <style>
        @php
            $size = $settings->card_size ?? 'a4';
            $pageSize = match ($size) { 'a5' => 'A5', 'letter' => 'letter', default => 'A4' };
        @endphp
        @page { size: {{ $pageSize }}; margin: 1.2cm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; font-size: 11pt; }
        .card { page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        .photo-wrap { text-align: center; margin-bottom: 18pt; }
        .photo { width: 100%; max-height: 340pt; }
        .header { border-bottom: 2pt solid #1f2937; padding-bottom: 8pt; margin-bottom: 12pt; }
        .artist { font-size: 12pt; font-weight: bold; color: #374151; margin-bottom: 2pt; }
        h1 { margin: 0; font-size: 18pt; font-style: italic; font-weight: normal; }
        .price { font-size: 14pt; font-weight: bold; float: right; margin-top: -22pt; }
        .price-or { font-size: 10pt; color: #6b7280; font-weight: normal; float: right; margin-top: -22pt; }
        .specs { width: 100%; border-collapse: collapse; margin-bottom: 14pt; }
        .specs td { padding: 3pt 0; vertical-align: top; }
        .specs td.label { color: #6b7280; font-weight: bold; width: 25%; padding-right: 12pt; }
        .description { line-height: 1.5; color: #374151; }
        .footer { margin-top: 24pt; padding-top: 10pt; border-top: 1pt dashed #d1d5db; font-size: 9pt; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
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
            @if ($artwork->primary_image && file_exists(public_path('storage/'.$artwork->primary_image)))
                <div class="photo-wrap">
                    <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
                </div>
            @endif

            <div class="header">
                @if ($settings->card_show_price ?? true)
                    @if ($artwork->price && ! $artwork->price_on_request)
                        <div class="price">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</div>
                    @elseif ($artwork->price_on_request)
                        <div class="price-or">Price on request</div>
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
                @if ($artwork->inventory_id)<tr><td class="label">Inv. ID</td><td style="font-family: monospace;">{{ $artwork->inventory_id }}</td></tr>@endif
            </table>

            @if ($artwork->description)
                <div class="description">{{ $artwork->description }}</div>
            @endif

            @php
                $footer = $settings->card_footer_text
                    ?: trim(($settings->company_name ?? '').($settings->email ? ' · '.$settings->email : ''));
            @endphp
            @if (filled($footer))
                <div class="footer">{{ $footer }}</div>
            @endif
        </div>
    @endforeach
</body>
</html>
