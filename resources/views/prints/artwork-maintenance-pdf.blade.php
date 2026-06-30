<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance report — {{ $artwork->title }}</title>
    <style>
        @page { size: A4; margin: 1.2cm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; font-size: 11pt; }
        .report-label { text-transform: uppercase; letter-spacing: 1pt; font-size: 8pt; color: #6b7280; font-weight: bold; margin-bottom: 4pt; }
        .doc-title { font-size: 16pt; font-weight: bold; margin: 0 0 14pt; }
        .photo-wrap { text-align: center; margin-bottom: 16pt; }
        .photo { max-width: 100%; max-height: 260pt; }
        .header { border-bottom: 2pt solid #1f2937; padding-bottom: 8pt; margin-bottom: 14pt; }
        .artist { font-size: 11pt; font-weight: bold; color: #374151; margin-bottom: 2pt; }
        .work-title { font-size: 14pt; font-style: italic; }
        .inv { float: right; font-family: monospace; font-size: 9pt; color: #6b7280; margin-top: 4pt; }
        .specs { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
        .specs td { padding: 2pt 0; vertical-align: top; }
        .specs td.label { color: #6b7280; font-weight: bold; width: 32%; padding-right: 12pt; font-size: 9pt; }
        .section-label { display: block; margin: 10pt 0 4pt; color: #6b7280; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.8pt; font-weight: bold; }
        .badge { display: inline-block; padding: 2pt 7pt; border-radius: 10pt; font-size: 9pt; font-weight: bold; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        .status-returned    { background: #d1fae5; color: #065f46; }
        .status-none        { background: #f3f4f6; color: #6b7280; }
        .notes { line-height: 1.45; color: #374151; background: #f9fafb; padding: 6pt 8pt; border-left: 2pt solid #d1d5db; white-space: pre-wrap; font-size: 10pt; }
        .summary { margin: 10pt 0 14pt; padding: 8pt 10pt; background: #f9fafb; border-radius: 4pt; }
        .summary .label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.6pt; color: #6b7280; font-weight: bold; }
        .summary .total { font-size: 14pt; font-weight: bold; }
        .record { margin-top: 12pt; padding: 8pt 10pt; border: 1pt solid #e5e7eb; border-radius: 4pt; page-break-inside: avoid; }
        .record-header { border-bottom: 1pt dashed #d1d5db; padding-bottom: 4pt; margin-bottom: 6pt; }
        .rec-no { font-size: 9pt; text-transform: uppercase; letter-spacing: 0.6pt; color: #6b7280; font-weight: bold; }
        .doc-list { padding-left: 14pt; margin: 4pt 0; font-size: 10pt; }
        .doc-list li { margin: 1pt 0; }
        .photo-row img { width: 110pt; height: 110pt; object-fit: cover; margin: 0 3pt 3pt 0; border: 1pt solid #e5e7eb; }
        .footer { margin-top: 20pt; padding-top: 8pt; border-top: 1pt dashed #d1d5db; font-size: 9pt; color: #6b7280; text-align: center; }
        .empty { color: #9ca3af; font-style: italic; }
        .cost { font-weight: bold; }
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

        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');
    @endphp

    <div class="report-label">Maintenance report</div>
    <div class="doc-title">{{ $settings->company_name ?? config('app.name', 'Art DB') }}</div>

    @if ($artwork->primary_image && file_exists(public_path('storage/'.$artwork->primary_image)))
        <div class="photo-wrap">
            <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
        </div>
    @endif

    <div class="header">
        @if ($artwork->inventory_id)<div class="inv">{{ $artwork->inventory_id }}</div>@endif
        <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
        <div class="work-title">{{ $artwork->title }}@if ($year), {{ $year }}@endif</div>
    </div>

    <table class="specs">
        @if ($artwork->medium?->name)<tr><td class="label">Medium</td><td>{{ $artwork->medium->name }}</td></tr>@endif
        @if ($artwork->materials)<tr><td class="label">Materials</td><td>{{ $artwork->materials }}</td></tr>@endif
        @if ($dims)<tr><td class="label">Dimensions</td><td>{{ $dims }} cm</td></tr>@endif
    </table>

    <div class="summary">
        <div class="label">Overall status</div>
        <div style="margin-top:3pt;"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></div>
        @if ($total > 0)
            <div class="label" style="margin-top:8pt;">Total cost</div>
            <div class="total">{{ number_format($total, 2, '.', ' ') }} {{ $artwork->currency ?? 'EUR' }}</div>
        @endif
    </div>

    @if ($maintenances->isEmpty())
        <div class="notes empty">No maintenance records yet for this artwork.</div>
    @else
        @foreach ($maintenances as $idx => $m)
            @php
                $mStatus = $m->restoration_returned_at ? 'returned' : 'in_progress';
                $mStatusLabel = $m->restoration_returned_at ? 'Returned' : 'In restoration';
                $sent     = $m->restoration_date?->format('d. m. Y');
                $returned = $m->restoration_returned_at?->format('d. m. Y');
                $hasContact = filled($m->restorer_name) || filled($m->restorer_email) || filled($m->restorer_phone);
                $docs   = (array) ($m->documents ?? []);
                $photos = (array) ($m->photos ?? []);
            @endphp
            <div class="record">
                <div class="record-header">
                    <span class="rec-no">Record #{{ $idx + 1 }}{{ $sent ? ' · '.$sent : '' }}</span>
                    <span class="badge status-{{ str_replace('_', '-', $mStatus) }}" style="float:right;">{{ $mStatusLabel }}</span>
                </div>

                <table class="specs">
                    @if ($sent)<tr><td class="label">Sent</td><td>{{ $sent }}</td></tr>@endif
                    @if ($returned)
                        <tr><td class="label">Returned</td><td>{{ $returned }}</td></tr>
                    @elseif ($sent)
                        <tr><td class="label">Returned</td><td class="empty">— still in restoration</td></tr>
                    @endif
                    @if ($m->restoration_price)
                        <tr><td class="label">Cost</td><td class="cost">{{ number_format((float) $m->restoration_price, 2, '.', ' ') }} {{ $artwork->currency ?? 'EUR' }}</td></tr>
                    @endif
                </table>

                @if ($hasContact)
                    <span class="section-label">Restorer</span>
                    <table class="specs">
                        @if ($m->restorer_name)<tr><td class="label">Name / studio</td><td>{{ $m->restorer_name }}</td></tr>@endif
                        @if ($m->restorer_email)<tr><td class="label">Email</td><td>{{ $m->restorer_email }}</td></tr>@endif
                        @if ($m->restorer_phone)<tr><td class="label">Phone</td><td>{{ $m->restorer_phone }}</td></tr>@endif
                    </table>
                @endif

                @if (filled($m->restoration_notes))
                    <span class="section-label">Notes</span>
                    <div class="notes">{{ $m->restoration_notes }}</div>
                @endif

                @if (! empty($docs))
                    <span class="section-label">Documents</span>
                    <ul class="doc-list">
                        @foreach ($docs as $doc)
                            <li>{{ basename($doc) }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($photos))
                    <span class="section-label">Photos</span>
                    <div class="photo-row">
                        @foreach ($photos as $photo)
                            @if (file_exists(public_path('storage/'.$photo)))
                                <img src="{{ public_path('storage/'.$photo) }}" alt="">
                            @endif
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
    <div class="footer">
        @if (filled($footer)){{ $footer }} · @endif Maintenance report generated {{ now()->format('d. m. Y') }}
    </div>
</body>
</html>
