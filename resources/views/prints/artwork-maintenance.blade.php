<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance report — {{ $artwork->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 0; padding: 2.5rem 1.5rem; background: #f3f4f6; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 2.5rem 3rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .actions { max-width: 820px; margin: 0 auto 1rem; display: flex; gap: 0.5rem; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; padding: 0.5rem 0.9rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
        .actions button.primary { background: #1f2937; color: #fff; border-color: #1f2937; }
        .report-label { text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.78rem; color: #6b7280; margin-bottom: 0.3rem; font-weight: 600; }
        .doc-title { font-size: 1.4rem; font-weight: 700; margin: 0 0 1.25rem; }
        .photo { width: 100%; max-height: 380px; object-fit: contain; background: #f9fafb; border-radius: 4px; margin-bottom: 1.5rem; display: block; }
        .photo-placeholder { width: 100%; aspect-ratio: 4/3; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 1.5rem; margin-bottom: 1.5rem; }
        .header-row { border-bottom: 2px solid #1f2937; padding-bottom: 0.75rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
        .header-row .artist { font-size: 1rem; font-weight: 600; color: #374151; }
        .header-row h2 { margin: 0; font-size: 1.3rem; font-style: italic; font-weight: normal; }
        .header-row .inv { font-family: ui-monospace, Menlo, monospace; font-size: 0.85rem; color: #6b7280; white-space: nowrap; }
        .status-badge { display: inline-block; padding: 0.25rem 0.7rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        .status-returned    { background: #d1fae5; color: #065f46; }
        .specs { display: grid; grid-template-columns: max-content 1fr; gap: 0.4rem 1.5rem; font-size: 0.95rem; margin-bottom: 0.5rem; }
        .specs dt { color: #6b7280; font-weight: 600; }
        .specs dd { margin: 0; }
        .summary { margin-top: 1.5rem; padding: 1rem 1.25rem; background: #f9fafb; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .summary .label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; font-weight: 600; }
        .summary .total { font-size: 1.4rem; font-weight: 700; }
        .record { margin-top: 1.5rem; padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; }
        .record-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #d1d5db; }
        .record-header .rec-no { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; font-weight: 600; }
        .section-label { display: block; margin: 0.8rem 0 0.3rem; color: #6b7280; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
        .notes { line-height: 1.55; color: #374151; white-space: pre-wrap; background: #f9fafb; padding: 0.8rem 1rem; border-radius: 4px; border-left: 3px solid #d1d5db; font-size: 0.92rem; }
        .doc-list { padding-left: 1rem; margin: 0.3rem 0; font-size: 0.92rem; }
        .doc-list li { margin: 0.15rem 0; }
        .photos-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(150px,1fr)); gap: 0.4rem; }
        .photos-grid img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; display: block; border: 1px solid #e5e7eb; }
        .gallery-meta { margin-top: 2rem; padding-top: 1rem; border-top: 1px dashed #d1d5db; font-size: 0.85rem; color: #6b7280; text-align: center; }
        .empty { color: #9ca3af; font-style: italic; }
        .cost { font-weight: 700; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; padding: 1.5rem; }
            .actions, .no-print { display: none !important; }
            @page { size: A4; margin: 1.2cm; }
            .record { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php
        $year = $artwork->year_created;
        if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
            $year = $artwork->year_created.'–'.$artwork->year_created_end;
        }

        $maintenances = $artwork->maintenances->sortBy([['restoration_date', 'asc']])->values();
        $total = (float) $maintenances->sum('restoration_price');
        $openCount = $maintenances->whereNull('restoration_returned_at')->count();
        $overallStatus = $maintenances->isEmpty() ? 'none' : ($openCount > 0 ? 'in_progress' : 'returned');
        $statusLabel = match ($overallStatus) {
            'in_progress' => 'In restoration — '.$openCount.' open record'.($openCount > 1 ? 's' : ''),
            'returned'    => 'All records returned ('.$maintenances->count().')',
            default       => 'No maintenance records',
        };
        $statusClass = 'status-'.str_replace('_', '-', $overallStatus);
    @endphp

    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('artworks.pdf.maintenance', $artwork) }}">Download PDF</a>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="sheet">
        <div class="report-label">Maintenance report</div>
        <h1 class="doc-title">{{ $settings->company_name ?? 'Project Arch' }}</h1>

        @if ($artwork->primary_image)
            <img class="photo" src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}" alt="{{ $artwork->title }}">
        @else
            <div class="photo-placeholder">no photo</div>
        @endif

        <div class="header-row">
            <div>
                <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
                <h2>{{ $artwork->title }}@if ($year), {{ $year }}@endif</h2>
            </div>
            @if ($artwork->inventory_id)
                <div class="inv">{{ $artwork->inventory_id }}</div>
            @endif
        </div>

        <dl class="specs">
            @if ($artwork->medium?->name)<dt>Medium</dt><dd>{{ $artwork->medium->name }}</dd>@endif
            @if ($artwork->materials)<dt>Materials</dt><dd>{{ $artwork->materials }}</dd>@endif
            @php
                $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
            @endphp
            @if ($dims)<dt>Dimensions</dt><dd>{{ $dims }} cm</dd>@endif
        </dl>

        <div class="summary">
            <div>
                <div class="label">Overall status</div>
                <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            @if ($total > 0)
                <div style="text-align:right;">
                    <div class="label">Total cost</div>
                    <div class="total">{{ number_format($total, 2, '.', ' ') }} {{ $artwork->currency ?? 'EUR' }}</div>
                </div>
            @endif
        </div>

        @if ($maintenances->isEmpty())
            <div class="notes empty" style="margin-top:1.5rem;">No maintenance records yet for this artwork.</div>
        @else
            @foreach ($maintenances as $idx => $m)
                @php
                    $mStatus = $m->restoration_returned_at ? 'returned' : 'in_progress';
                    $mStatusLabel = $m->restoration_returned_at ? '✅ Returned' : '🔧 In restoration';
                    $sent     = $m->restoration_date?->format('d. m. Y');
                    $returned = $m->restoration_returned_at?->format('d. m. Y');
                    $hasContact = filled($m->restorer_name) || filled($m->restorer_email) || filled($m->restorer_phone);
                    $docs   = (array) ($m->documents ?? []);
                    $photos = (array) ($m->photos ?? []);
                @endphp
                <div class="record">
                    <div class="record-header">
                        <span class="rec-no">Record #{{ $idx + 1 }}{{ $sent ? ' · '.$sent : '' }}</span>
                        <span class="status-badge status-{{ str_replace('_', '-', $mStatus) }}">{{ $mStatusLabel }}</span>
                    </div>

                    <dl class="specs">
                        @if ($sent)<dt>Sent</dt><dd>{{ $sent }}</dd>@endif
                        @if ($returned)
                            <dt>Returned</dt><dd>{{ $returned }}</dd>
                        @elseif ($sent)
                            <dt>Returned</dt><dd class="empty">— still in restoration</dd>
                        @endif
                        @if ($m->restoration_price)
                            <dt>Cost</dt>
                            <dd class="cost">{{ number_format((float) $m->restoration_price, 2, '.', ' ') }} {{ $artwork->currency ?? 'EUR' }}</dd>
                        @endif
                    </dl>

                    @if ($hasContact)
                        <span class="section-label">Restorer</span>
                        <dl class="specs">
                            @if ($m->restorer_name)<dt>Name / studio</dt><dd>{{ $m->restorer_name }}</dd>@endif
                            @if ($m->restorer_email)<dt>Email</dt><dd>{{ $m->restorer_email }}</dd>@endif
                            @if ($m->restorer_phone)<dt>Phone</dt><dd>{{ $m->restorer_phone }}</dd>@endif
                        </dl>
                    @endif

                    @if (filled($m->restoration_notes))
                        <span class="section-label">Notes</span>
                        <div class="notes">{{ $m->restoration_notes }}</div>
                    @endif

                    @if (! empty($docs))
                        <span class="section-label">Documents</span>
                        <ul class="doc-list">
                            @foreach ($docs as $doc)
                                <li><a href="{{ \Illuminate\Support\Facades\Storage::url($doc) }}" target="_blank">{{ basename($doc) }}</a></li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! empty($photos))
                        <span class="section-label">Photos</span>
                        <div class="photos-grid">
                            @foreach ($photos as $photo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($photo) }}" alt="">
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif

        @php
            $footer = $settings->card_footer_text
                ?: trim(($settings->company_name ?? '').($settings->email ? ' · '.$settings->email : ''));
        @endphp
        @if (filled($footer))
            <div class="gallery-meta">{{ $footer }} · Maintenance report generated {{ now()->format('d. m. Y') }}</div>
        @endif
    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
