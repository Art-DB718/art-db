<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label — {{ $artwork->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; padding: 2rem; background: #f3f4f6; }
        .actions { max-width: 320px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: center; }
        .actions button, .actions a { font: inherit; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }
        .label {
            width: 320px;             /* ~ 85 × 55 mm */
            min-height: 200px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            line-height: 1.4;
        }
        .label .artist { font-size: 1.05rem; font-weight: 700; }
        .label .title  { font-size: 0.95rem; font-style: italic; margin-top: 0.1rem; }
        .label .specs  { margin-top: 0.6rem; font-size: 0.8rem; color: #4b5563; line-height: 1.45; }
        .label .specs strong { color: #1f2937; }
        .label .inv    { margin-top: 0.6rem; font-size: 0.7rem; color: #6b7280; font-family: 'SF Mono', Menlo, monospace; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions, .no-print { display: none !important; }
            .label { margin: 1cm; box-shadow: none; }
        }
    </style>
    @php
        $size = $settings->label_size ?? 'standard';
        $w = match ($size) {
            'small'    => '227px',
            'large'    => '397px',
            'a6'       => '559px',
            default    => '320px',
        };
        $h = match ($size) {
            'small'    => '151px',
            'large'    => '264px',
            'a6'       => '397px',
            default    => '200px',
        };
        $pad = match ($size) {
            'small'    => '0.5rem 0.7rem',
            'large'    => '1.1rem 1.4rem',
            'a6'       => '1.5rem 1.75rem',
            default    => '1rem 1.25rem',
        };
        $artistFs = match ($size) {
            'small'    => '0.8rem',
            'large'    => '1.15rem',
            'a6'       => '1.35rem',
            default    => '1.05rem',
        };
        $titleFs = match ($size) {
            'small'    => '0.7rem',
            'large'    => '1.05rem',
            'a6'       => '1.2rem',
            default    => '0.95rem',
        };
        $specsFs = match ($size) {
            'small'    => '0.6rem',
            'large'    => '0.85rem',
            'a6'       => '0.9rem',
            default    => '0.8rem',
        };
        $invFs = match ($size) {
            'small'    => '0.55rem',
            'large'    => '0.75rem',
            'a6'       => '0.8rem',
            default    => '0.7rem',
        };
        $pageSize = match ($size) {
            'small'    => '60mm 40mm',
            'large'    => '105mm 70mm',
            'a6'       => '148mm 105mm',
            default    => '85mm 55mm',
        };
    @endphp
    <style>
        /* Label size overrides — driven by Design printouts → Artwork Label → Label size */
        .label         { width: {{ $w }} !important; min-height: {{ $h }} !important; padding: {{ $pad }} !important; }
        .label .artist { font-size: {{ $artistFs }} !important; }
        .label .title  { font-size: {{ $titleFs }} !important; }
        .label .specs  { font-size: {{ $specsFs }} !important; }
        .label .inv    { font-size: {{ $invFs }} !important; }
        @page          { size: {{ $pageSize }}; margin: 0; }
        @media print {
            .label     { margin: 0 !important; border: none; min-height: 100vh; }
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
    @endphp

    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="label">
        @if ($settings->label_show_logo && $settings->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}"
                 alt=""
                 style="max-height:24px;max-width:60%;display:block;margin-bottom:0.4rem;opacity:0.9;">
        @endif
        <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
        <div class="title">{{ $artwork->title }}@if ($year), {{ $year }}@endif</div>

        <div class="specs">
            @if ($artwork->medium?->name)
                <div>{{ $artwork->medium->name }}{{ $artwork->materials ? ' — '.$artwork->materials : '' }}</div>
            @endif
            @if ($settings->label_show_dimensions && $dims)
                <div>{{ $dims }} cm</div>
            @endif
            @if ($artwork->edition_number && $artwork->edition_total)
                <div>Edition {{ $artwork->edition_number }}/{{ $artwork->edition_total }}</div>
            @endif
            @if ($settings->label_show_price && $artwork->price && ! $artwork->price_on_request)
                <div style="margin-top:0.35rem;font-size:0.9rem;color:#1f2937;font-weight:600;">
                    {{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}
                </div>
            @endif
        </div>

    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
