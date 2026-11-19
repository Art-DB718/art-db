<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('condition_report.title') }} — {{ $artwork->title }}</title>
    <style>
        /*
         * SNG-style Condition Report — multilingual. Static chrome lives
         * in lang/{locale}/condition_report.php so the PDF can be issued
         * in any language the Print menu offers (en/sk/de/cs/hu/pl/uk/sr
         * /hr/fr/nl). User-entered content (artwork title, notes, people's
         * names) stays as typed. Layout mirrors the paper protocol Kat
         * uses with borrowers.
         */
        @page { size: A4; margin: 2cm 2cm 2.5cm 2cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10pt; line-height: 1.4; }

        .header-brand { text-align: center; margin-bottom: 14pt; color: #374151; }
        .header-brand img { max-height: 70pt; max-width: 240pt; }
        .header-brand .wordmark { font-family: DejaVu Serif, serif; font-size: 20pt; letter-spacing: 0.3em; text-transform: uppercase; color: #374151; }

        h1.title { font-family: DejaVu Serif, serif; font-size: 18pt; text-align: center; margin: 4pt 0 22pt; font-weight: bold; }

        .meta-row { margin-bottom: 6pt; }
        .meta-row .lbl { font-size: 9pt; color: #6b7280; }
        .meta-row .val { font-weight: bold; color: #111827; }

        .artwork-block { margin: 18pt 0 22pt; padding: 10pt 12pt; background: #f9fafb; border-left: 2pt solid #9ca3af; page-break-inside: avoid; }
        .artwork-block .photo { max-width: 110pt; max-height: 110pt; float: left; margin: 0 12pt 6pt 0; border: 1pt solid #e5e7eb; }
        .artwork-block .artist { font-size: 10pt; font-weight: bold; letter-spacing: 0.06em; text-transform: uppercase; }
        .artwork-block .work-title { font-style: italic; font-size: 11pt; margin: 2pt 0 4pt; }
        .artwork-block .inv { font-family: monospace; font-size: 8.5pt; color: #6b7280; }
        .artwork-block .specs { font-size: 9pt; color: #374151; margin-top: 4pt; }
        .clearfix::after { content: ""; display: table; clear: both; }

        h2.sec { font-family: DejaVu Serif, serif; font-size: 12pt; font-weight: bold; margin: 18pt 0 8pt; padding-bottom: 3pt; border-bottom: 1pt solid #9ca3af; }

        /* Report metadata badges: one compact row surfacing stage, rating, date. */
        .meta-badges { margin: 4pt 0 14pt; }
        .badge { display: inline-block; padding: 2pt 7pt; margin: 0 4pt 3pt 0; border-radius: 10pt; font-size: 9pt; font-weight: bold; background: #f3f4f6; color: #374151; }
        .badge-stage       { background: #e0e7ff; color: #3730a3; }
        .rating-excellent  { background: #d1fae5; color: #065f46; }
        .rating-good       { background: #d1fae5; color: #065f46; }
        .rating-fair       { background: #fef3c7; color: #92400e; }
        .rating-poor       { background: #fee2e2; color: #991b1b; }
        .badge-warn        { background: #fef3c7; color: #92400e; }

        .check-table { width: 100%; border-collapse: collapse; }
        .check-table td { vertical-align: top; padding: 2pt 4pt; width: 50%; font-size: 9.5pt; }
        .check { display: inline-block; width: 10pt; height: 10pt; border: 1pt solid #374151; margin-right: 6pt; vertical-align: middle; text-align: center; font-size: 10pt; line-height: 9pt; }
        .check.on { background: #111827; color: #ffffff; }

        .notes-block { margin-top: 10pt; padding: 8pt 10pt; background: #f9fafb; border-left: 2pt solid #d1d5db; white-space: pre-wrap; font-size: 10pt; min-height: 22pt; }

        .annotated { margin-top: 10pt; }
        .annotated img { max-width: 100%; max-height: 380pt; margin-bottom: 10pt; page-break-inside: avoid; }

        .signatures { margin-top: 22pt; page-break-inside: avoid; }
        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td { vertical-align: bottom; padding: 18pt 6pt 2pt; width: 50%; }
        .signatures .line { border-top: 1pt solid #374151; padding-top: 3pt; font-size: 8.5pt; color: #6b7280; }
        .signatures .caption { font-size: 9pt; color: #374151; font-weight: bold; margin-bottom: 14pt; }

        .footer { position: fixed; bottom: 1.2cm; left: 2cm; right: 2cm; font-size: 8.5pt; color: #6b7280; text-align: center; border-top: 1pt solid #e5e7eb; padding-top: 4pt; }
    </style>
</head>
<body>
    @php
        $r = is_array($artwork->condition_report) ? $artwork->condition_report : [];
        $bodyDamages = \App\Support\ConditionReport::bodyDamages();
        $frameDamages = \App\Support\ConditionReport::frameDamages();
        $checkedBody = $r['body_damages'] ?? [];
        $checkedFrame = $r['frame_damages'] ?? [];
        $annotated = $r['annotated_images'] ?? [];

        $year = $artwork->year_created;
        if ($artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
            $year = $artwork->year_created.'–'.$artwork->year_created_end;
        }
        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $dims = collect([$fmt($artwork->height_cm), $fmt($artwork->width_cm), $fmt($artwork->depth_cm)])->filter()->implode(' × ');

        // Label lookup: prefer the translation file for the active locale,
        // fall back to the English label baked into ConditionReport.php so
        // a missing translation never prints a raw "condition_report.body.x"
        // string.
        $translate = function (string $group, string $key, array $fallbackList) {
            $full = 'condition_report.'.$group.'.'.$key;
            $translated = __($full);
            if ($translated === $full) {
                return $fallbackList[$key]['en'] ?? $key;
            }
            return $translated;
        };

        $renderCheck = function (array $list, array $checked, string $group) use ($translate) {
            $items = [];
            foreach ($list as $key => $labels) {
                $on = in_array($key, $checked, true) ? 'on' : '';
                $tick = $on === 'on' ? '✓' : '';
                $items[] = '<span class="check '.$on.'">'.$tick.'</span>'
                    . e($translate($group, $key, $list));
            }
            $half = (int) ceil(count($items) / 2);
            $left = array_slice($items, 0, $half);
            $right = array_slice($items, $half);
            $max = max(count($left), count($right));
            $out = '<table class="check-table">';
            for ($i = 0; $i < $max; $i++) {
                $out .= '<tr>';
                $out .= '<td>' . ($left[$i] ?? '&nbsp;') . '</td>';
                $out .= '<td>' . ($right[$i] ?? '&nbsp;') . '</td>';
                $out .= '</tr>';
            }
            $out .= '</table>';
            return $out;
        };

        $addressParts = array_filter([
            trim(collect([$settings->address_line1, $settings->address_line2, $settings->postal_code, $settings->city])->filter()->implode(', ')),
            $settings->phone   ? 'mobil: '.$settings->phone      : null,
            $settings->email   ? 'email: '.$settings->email      : null,
            $settings->website ? 'web: '.$settings->website      : null,
        ]);
        $footerLine = implode(', ', $addressParts);

        $ratingClass = [
            'excellent' => 'rating-excellent',
            'good'      => 'rating-good',
            'fair'      => 'rating-fair',
            'poor'      => 'rating-poor',
        ];
    @endphp

    <div class="header-brand">
        @if ($settings->logo_path && file_exists(public_path('storage/'.$settings->logo_path)))
            <img src="{{ public_path('storage/'.$settings->logo_path) }}" alt="">
        @elseif ($settings->company_name)
            <div class="wordmark">{{ $settings->company_name }}</div>
        @endif
    </div>

    <h1 class="title">{{ __('condition_report.title') }}</h1>

    {{-- Report metadata badges --}}
    @if (!empty($r['stage']) || !empty($r['overall_rating']) || !empty($r['report_date']) || !empty($r['needs_conservator_review']))
        <div class="meta-badges">
            @if (!empty($r['stage']))
                <span class="badge badge-stage">{{ __('condition_report.stage_label') }}: {{ __('condition_report.stages.'.$r['stage']) }}</span>
            @endif
            @if (!empty($r['overall_rating']))
                <span class="badge {{ $ratingClass[$r['overall_rating']] ?? '' }}">{{ __('condition_report.overall_label') }}: {{ __('condition_report.ratings.'.$r['overall_rating']) }}</span>
            @endif
            @if (!empty($r['report_date']))
                <span class="badge">{{ __('condition_report.report_date_label') }}: {{ \Carbon\Carbon::parse($r['report_date'])->format('d.m.Y') }}</span>
            @endif
            @if (!empty($r['needs_conservator_review']))
                <span class="badge badge-warn">⚠ {{ __('condition_report.needs_conservator_review') }}</span>
            @endif
        </div>
    @endif

    @if (!empty($r['reason']))
        <div class="meta-row"><span class="lbl">{{ __('condition_report.reason') }}:</span> {{ $r['reason'] }}</div>
    @endif
    @if (!empty($r['examination_limitations']))
        <div class="meta-row"><span class="lbl">{{ __('condition_report.examination_limitations') }}:</span> {{ $r['examination_limitations'] }}</div>
    @endif

    {{-- Exhibition / Loan context --}}
    @if (!empty($r['exhibition_title'])) <div class="meta-row"><span class="lbl">{{ __('condition_report.exhibition_title') }}:</span> <span class="val">{{ $r['exhibition_title'] }}</span></div> @endif
    @if (!empty($r['institution'])) <div class="meta-row"><span class="lbl">{{ __('condition_report.institution') }}:</span> <span class="val">{{ $r['institution'] }}</span></div> @endif
    <div class="meta-row">
        @if (!empty($r['place'])) <span class="lbl">{{ __('condition_report.place') }}:</span> <span class="val">{{ $r['place'] }}</span> @endif
        @if (!empty($r['duration'])) &nbsp;&nbsp; <span class="lbl">{{ __('condition_report.duration') }}:</span> <span class="val">{{ $r['duration'] }}</span> @endif
    </div>
    @if (!empty($r['contact_person'])) <div class="meta-row"><span class="lbl">{{ __('condition_report.contact_person') }}:</span> <span class="val">{{ $r['contact_person'] }}</span></div> @endif
    @if (!empty($r['curator'])) <div class="meta-row"><span class="lbl">{{ __('condition_report.curator') }}:</span> <span class="val">{{ $r['curator'] }}</span></div> @endif

    {{-- Installation / Transport --}}
    @if (!empty($r['installation_per_agreement']) || !empty($r['installation_notes']) || !empty($r['transport_per_agreement']) || !empty($r['transport_notes']))
        <div style="margin-top: 14pt;">
            @if (!empty($r['installation_per_agreement']) || !empty($r['installation_notes']))
                <div class="meta-row">
                    <strong>{{ __('condition_report.installation_conditions') }}</strong>
                    @if (!empty($r['installation_per_agreement']))
                        &nbsp;&nbsp;<span class="check on">✓</span> {{ __('condition_report.per_agreement') }}
                    @endif
                </div>
                @if (!empty($r['installation_notes']))
                    <div class="meta-row"><span class="lbl">{{ __('condition_report.notes') }}:</span> {{ $r['installation_notes'] }}</div>
                @endif
            @endif
            @if (!empty($r['transport_per_agreement']) || !empty($r['transport_notes']))
                <div class="meta-row" style="margin-top:6pt;">
                    <strong>{{ __('condition_report.transport_conditions') }}</strong>
                    @if (!empty($r['transport_per_agreement']))
                        &nbsp;&nbsp;<span class="check on">✓</span> {{ __('condition_report.per_agreement') }}
                    @endif
                </div>
                @if (!empty($r['transport_notes']))
                    <div class="meta-row"><span class="lbl">{{ __('condition_report.notes') }}:</span> {{ $r['transport_notes'] }}</div>
                @endif
            @endif
        </div>
    @endif

    {{-- Artwork identity --}}
    <div class="artwork-block clearfix">
        @if ($artwork->primary_image && file_exists(public_path('storage/'.$artwork->primary_image)))
            <img class="photo" src="{{ public_path('storage/'.$artwork->primary_image) }}" alt="">
        @endif
        <div class="artist">{{ $artwork->artist?->display_name ?? '—' }}</div>
        <div class="work-title"><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
        @if ($artwork->inventory_id) <div class="inv">{{ $artwork->inventory_id }}</div> @endif
        <div class="specs">
            @if ($artwork->medium?->name) {{ art_term_en($artwork->medium->name, $artwork->medium->name_en ?? null) }}@endif
            @if ($dims) · {{ $dims }} cm @endif
            @if ($artwork->materials) · {{ $artwork->materials }} @endif
        </div>
    </div>

    {{-- Body damages --}}
    <h2 class="sec">{{ __('condition_report.section_body') }}</h2>
    {!! $renderCheck($bodyDamages, is_array($checkedBody) ? $checkedBody : [], 'body') !!}
    @if (!empty($r['body_notes']))
        <div class="notes-block">{{ $r['body_notes'] }}</div>
    @endif

    {{-- Frame damages --}}
    <h2 class="sec">{{ __('condition_report.section_frame') }}</h2>
    {!! $renderCheck($frameDamages, is_array($checkedFrame) ? $checkedFrame : [], 'frame') !!}
    @if (!empty($r['frame_notes']))
        <div class="notes-block">{{ $r['frame_notes'] }}</div>
    @endif

    {{-- Annotated images --}}
    @if (!empty($annotated))
        <h2 class="sec">{{ __('condition_report.section_annotated') }}</h2>
        <div class="annotated">
            @foreach ($annotated as $path)
                @if (file_exists(public_path('storage/'.$path)))
                    <img src="{{ public_path('storage/'.$path) }}" alt="">
                @endif
            @endforeach
        </div>
    @endif

    {{-- Signatures --}}
    @php
        $roleKey = $r['checked_by_role'] ?? null;
        $roleOnLine = '';
        if ($roleKey) {
            $t = __('condition_report.roles.'.$roleKey);
            // Fallback when the translation is missing or deliberately empty
            // (e.g. "other" stays blank in every language).
            if ($t !== 'condition_report.roles.'.$roleKey && $t !== '') {
                $roleOnLine = $t;
            }
        }
    @endphp
    <div class="signatures">
        <div class="caption">{{ __('condition_report.handing_over') }}</div>
        <table>
            <tr>
                <td>
                    <div class="line">
                        {{ $r['checked_by'] ?? '' }}@if ($roleOnLine) <span style="color:#6b7280; font-weight:normal;">({{ $roleOnLine }})</span>@endif
                        &nbsp;
                    </div>
                    <div class="lbl">{{ __('condition_report.checked_by') }}</div>
                </td>
                <td>
                    <div class="line">&nbsp;</div>
                    <div class="lbl">{{ __('condition_report.signature') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="line">
                        {{ $r['checked_place'] ?? '' }}
                        &nbsp;
                    </div>
                    <div class="lbl">{{ __('condition_report.place_short') }}</div>
                </td>
                <td>
                    <div class="line">
                        @if (!empty($r['checked_date']))
                            {{ \Carbon\Carbon::parse($r['checked_date'])->format('d.m.Y') }}
                        @endif
                        &nbsp;
                    </div>
                    <div class="lbl">{{ __('condition_report.date_short') }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="line">
                        {{ $r['borrower_recipient'] ?? '' }}
                        &nbsp;
                    </div>
                    <div class="lbl">{{ __('condition_report.borrower_recipient') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if ($footerLine)
        <div class="footer">{{ $footerLine }}</div>
    @endif
</body>
</html>
