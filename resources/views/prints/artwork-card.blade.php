<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Artwork — {{ $artwork->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; padding: 2.5rem 1.5rem; background: #f3f4f6; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 2.5rem 3rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .actions { max-width: 820px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }
        .photo { width: 100%; max-height: 460px; object-fit: contain; background: #f9fafb; border-radius: 4px; margin-bottom: 1.5rem; display: block; }
        .photo-placeholder { width: 100%; aspect-ratio: 4/3; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2rem; margin-bottom: 1.5rem; }
        .title-row { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 2px solid #1f2937; padding-bottom: 0.75rem; margin-bottom: 1rem; gap: 1rem; }
        h1 { margin: 0; font-size: 1.6rem; font-style: italic; }
        .artist { font-size: 1.1rem; font-weight: 600; color: #374151; margin-bottom: 0.15rem; }
        .price { font-size: 1.4rem; font-weight: 700; white-space: nowrap; }
        .specs { display: grid; grid-template-columns: max-content 1fr; gap: 0.4rem 1.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .specs dt { color: #6b7280; font-weight: 600; }
        .specs dd { margin: 0; }
        .description { line-height: 1.6; color: #374151; white-space: pre-wrap; }
        .gallery-meta { margin-top: 2rem; padding-top: 1rem; border-top: 1px dashed #d1d5db; font-size: 0.85rem; color: #6b7280; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; padding: 1.5rem; }
            .actions, .no-print { display: none !important; }
        }
    </style>
    @php
        $size = $settings->card_size ?? 'a4';
        $sheetWidth = match ($size) {
            'a5'     => '580px',
            'letter' => '850px',
            default  => '820px',
        };
        $photoMax = match ($size) {
            'a5'     => '320px',
            'letter' => '480px',
            default  => '460px',
        };
        $padding = match ($size) {
            'a5'    => '1.75rem 2rem',
            default => '2.5rem 3rem',
        };
        $titleSize = match ($size) {
            'a5'    => '1.3rem',
            default => '1.6rem',
        };
        $pageSize = match ($size) {
            'a5'     => 'A5',
            'letter' => 'letter',
            default  => 'A4',
        };
    @endphp
    <style>
        /* Size-specific overrides driven by Design printouts → Artwork Card → Paper size */
        .sheet { max-width: {{ $sheetWidth }} !important; padding: {{ $padding }} !important; }
        .photo { max-height: {{ $photoMax }} !important; }
        h1     { font-size: {{ $titleSize }} !important; }
        @page  { size: {{ $pageSize }}; margin: 1.2cm; }
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
            ? $artwork->edition_number.' / '.$artwork->edition_total
            : ($artwork->edition_notes ?: null);
    @endphp

    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="sheet">
        @if ($artwork->primary_image)
            <img class="photo" src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}" alt="{{ $artwork->title }}">
        @else
            <div class="photo-placeholder">no photo</div>
        @endif

        <div class="title-row">
            <div>
                <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                <h1>{{ $artwork->title }}@if ($year), {{ $year }}@endif</h1>
            </div>
            @if ($settings->card_show_price)
                @if ($artwork->price && ! $artwork->price_on_request)
                    <div class="price">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</div>
                @elseif ($artwork->price_on_request)
                    <div class="price" style="font-size:1rem;color:#6b7280;font-weight:500;">Price on request</div>
                @endif
            @endif
        </div>

        <dl class="specs">
            @if ($artwork->medium?->name)<dt>Medium</dt><dd>{{ $artwork->medium->name }}</dd>@endif
            @if ($artwork->materials)<dt>Materials</dt><dd>{{ $artwork->materials }}</dd>@endif
            @if ($dims)<dt>Dimensions</dt><dd>{{ $dims }} cm</dd>@endif
            @if ($edition)<dt>Edition</dt><dd>{{ $edition }}</dd>@endif
            @if ($artwork->is_signed)
                <dt>Signed</dt>
                <dd>Yes{{ $artwork->signature_description ? ' — '.$artwork->signature_description : '' }}</dd>
            @endif
            @if ($artwork->is_framed)<dt>Framed</dt><dd>Yes</dd>@endif
        </dl>

        @if ($artwork->description)
            <div class="description">{{ $artwork->description }}</div>
        @endif

        @if ($settings->card_show_gallery && filled($artwork->gallery_images))
            <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px dashed #d1d5db;">
                <strong style="display:block;margin-bottom:0.5rem;color:#6b7280;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;">Gallery</strong>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:0.5rem;">
                    @foreach ((array) $artwork->gallery_images as $img)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($img) }}" alt=""
                             style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:4px;display:block;">
                    @endforeach
                </div>
            </div>
        @endif

        @if ($settings->card_show_provenance && $artwork->provenance)
            <div class="description" style="margin-top:1.25rem;padding-top:1rem;border-top:1px dashed #d1d5db;">
                <strong style="display:block;margin-bottom:0.3rem;color:#6b7280;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.08em;">Provenance</strong>
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
