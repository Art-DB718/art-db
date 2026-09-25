<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance report — {{ $artwork->title }}</title>
    <style>
        /*
         * Maintenance report — matches the card/certificate layout:
         * large centred logo header, artwork photo + specs stage,
         * maintenance records below, address footer pinned to page
         * bottom. A4 explicit.
         */
        @page { size: A4; margin: 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10.5pt; line-height: 1.5; }

        /* Header: centred logo, dominant. */
        .header { width: 100%; margin-bottom: 24pt; text-align: center; color: #374151; }
        .header img { max-height: 110pt; max-width: 360pt; }
        .header .wordmark {
            font-family: DejaVu Serif, serif;
            font-size: 30pt;
            letter-spacing: 0.35em;
            color: #374151;
            text-transform: uppercase;
        }
        .report-label { text-align: center; text-transform: uppercase; letter-spacing: 0.2em; font-size: 10pt; color: #6b7280; font-weight: bold; margin-bottom: 18pt; }

        /* Stage: photo + artwork meta share a common 80% centred column
           so text left edge lines up with the photo's left edge. */
        .stage       { width: 80%; margin: 0 auto; }
        .photo-wrap  { text-align: left; margin-bottom: 18pt; }
        .photo       { max-width: 100%; max-height: 320pt; }
        .artist      { font-size: 10.5pt; font-weight: bold; letter-spacing: 0.06em; text-transform: uppercase; }
        .work-title  { font-style: italic; font-size: 11pt; margin: 2pt 0 4pt; }
        .inv         { font-family: monospace; font-size: 9pt; color: #6b7280; margin-top: 3pt; }
        .specs       { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        .specs td    { padding: 3pt 0; vertical-align: top; }
        .specs td.label { color: #6b7280; width: 32%; padding-right: 12pt; font-size: 9.5pt; }

        /* Maintenance summary + records live in the same stage column. */
        .section-label { display: block; margin: 14pt 0 4pt; color: #6b7280; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.1em; font-weight: bold; }
        .badge { display: inline-block; padding: 2pt 7pt; border-radius: 10pt; font-size: 9pt; font-weight: bold; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        .status-returned    { background: #d1fae5; color: #065f46; }
        .status-none        { background: #f3f4f6; color: #6b7280; }
        .notes  { line-height: 1.5; color: #374151; background: #f9fafb; padding: 6pt 8pt; border-left: 2pt solid #d1d5db; white-space: pre-wrap; font-size: 10pt; }
        .summary { margin: 18pt 0 12pt; padding: 8pt 10pt; background: #f9fafb; border-radius: 4pt; }
        .summary .label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; font-weight: bold; }
        .summary .total { font-size: 14pt; font-weight: bold; }
        .record { margin-top: 12pt; padding: 8pt 10pt; border: 1pt solid #e5e7eb; border-radius: 4pt; page-break-inside: avoid; }
        .record-header { border-bottom: 1pt dashed #d1d5db; padding-bottom: 4pt; margin-bottom: 6pt; }
        .rec-no { font-size: 9pt; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; font-weight: bold; }
        .doc-list { padding-left: 14pt; margin: 4pt 0; font-size: 10pt; }
        .doc-list li { margin: 1pt 0; }
        .photo-row img { width: 110pt; height: 110pt; object-fit: cover; margin: 0 3pt 3pt 0; border: 1pt solid #e5e7eb; }
        .empty { color: #9ca3af; font-style: italic; }
        .cost { font-weight: bold; }

        /* Footer pinned to every page bottom. */
        .footer { position: fixed; bottom: 1.5cm; left: 2cm; right: 2cm; font-size: 9pt; color: #6b7280; text-align: center; }
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

        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);
    @endphp

    <div class="header">
        @if ($settings->logo_path)
            <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
        @elseif ($settings->company_name)
            <div class="wordmark">{{ $settings->company_name }}</div>
        @endif
    </div>

    <div class="report-label">Maintenance report</div>

    <div class="stage">
        @if ($artwork->primary_image && file_exists(public_path('storage/'.$artwork->primary_image)))
            <div class="photo-wrap">
                <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
            </div>
        @endif

        <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
        <div class="work-title"><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
        @if ($artwork->inventory_id)<div class="inv">{{ $artwork->inventory_id }}</div>@endif

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
    </div>{{-- /.stage --}}

    @if ($footerLine)
        <div class="footer">{{ $footerLine }}</div>
    @endif
</body>
</html>
