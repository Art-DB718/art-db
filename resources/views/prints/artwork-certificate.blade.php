<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate — {{ $artwork->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Georgia', 'Times New Roman', serif; color: #1f2937; margin: 0; padding: 2.5rem 1.5rem; background: #f3f4f6; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 4rem 4rem 3rem; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #d1d5db; }
        .actions { max-width: 820px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; font-family: Helvetica, Arial, sans-serif; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }
        .header { text-align: center; border-bottom: 3px double #1f2937; padding-bottom: 1.5rem; margin-bottom: 2rem; }
        .header img { max-height: 64px; max-width: 220px; margin-bottom: 1rem; }
        .company { font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; font-size: 0.9rem; color: #4b5563; }
        h1 { text-align: center; font-size: 2.2rem; letter-spacing: 0.15em; margin: 0 0 2rem; }
        .intro { text-align: center; font-style: italic; color: #4b5563; margin-bottom: 2rem; line-height: 1.6; }
        .artwork-block { display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 2rem; }
        .artwork-block img { width: 200px; height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; flex: none; }
        .artwork-block .placeholder { width: 200px; height: 200px; background: #f3f4f6; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex: none; }
        .artwork-info { flex: 1; line-height: 1.6; }
        .artwork-info .artist { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.25rem; }
        .artwork-info .title { font-size: 1.15rem; font-style: italic; margin-bottom: 0.75rem; }
        .artwork-info dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.3rem 1rem; font-size: 0.95rem; margin: 0; }
        .artwork-info dt { color: #6b7280; }
        .artwork-info dd { margin: 0; }
        .statement { margin: 2rem 0; padding: 1.25rem; border-left: 3px solid #1f2937; background: #f9fafb; line-height: 1.6; font-style: italic; }
        .signature-block { display: flex; gap: 2rem; margin-top: 3rem; }
        .signature-block > div { flex: 1; }
        .signature-line { border-bottom: 1px solid #4b5563; height: 2.5rem; margin-bottom: 0.4rem; }
        .signature-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; }
        .footer-ids { margin-top: 2rem; text-align: center; font-size: 0.75rem; color: #9ca3af; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; padding: 2rem; border: none; }
            .actions, .no-print { display: none !important; }
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
        $edition = $artwork->edition_number && $artwork->edition_total
            ? $artwork->edition_number.' / '.$artwork->edition_total
            : ($artwork->edition_notes ?: null);
    @endphp

    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="sheet">
        <div class="header">
            @if ($settings->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="">
            @endif
            <div class="company">{{ $settings->company_name ?? config('app.name', 'ArtDB') }}</div>
        </div>

        <h1>Certificate of Authenticity</h1>

        <div class="intro">
            @if (filled($settings->cert_intro))
                {!! $settings->cert_intro !!}
            @else
                This certificate confirms that the artwork described below is an original work
                by the named artist and was acquired from {{ $settings->company_name ?? 'us' }}.
            @endif
        </div>

        <div class="artwork-block">
            @if ($artwork->primary_image)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}" alt="">
            @else
                <div class="placeholder">—</div>
            @endif
            <div class="artwork-info">
                <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                <div class="title">{{ $artwork->title }}@if ($year), {{ $year }}@endif</div>
                <dl>
                    @if ($artwork->medium?->name)<dt>Medium</dt><dd>{{ $artwork->medium->name }}</dd>@endif
                    @if ($artwork->materials)<dt>Materials</dt><dd>{{ $artwork->materials }}</dd>@endif
                    @if ($dims)<dt>Dimensions</dt><dd>{{ $dims }} cm</dd>@endif
                    @if ($edition)<dt>Edition</dt><dd>{{ $edition }}</dd>@endif
                    @if ($artwork->is_signed)<dt>Signature</dt><dd>{{ $artwork->signature_description ?: 'Signed by the artist' }}</dd>@endif
                </dl>
            </div>
        </div>

        @if ($artwork->provenance)
            <div class="statement"><strong>Provenance:</strong><br>{{ $artwork->provenance }}</div>
        @endif

        <div class="signature-block">
            <div>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $settings->cert_signature_label ?: ('Issued by — '.($settings->company_name ?? 'Gallery')) }}</div>
            </div>
            <div>
                <div class="signature-line">{{ now()->format('d. m. Y') }}</div>
                <div class="signature-label">Date</div>
            </div>
        </div>

    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
