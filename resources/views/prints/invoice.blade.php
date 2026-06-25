<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 2.5rem 1.5rem;
            background: #f3f4f6;
        }
        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 2.5rem 3rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .header h1 { margin: 0; font-size: 2rem; letter-spacing: 0.15em; }
        .invoice-meta { text-align: right; font-size: 0.9rem; line-height: 1.6; }
        .invoice-number {
            font-family: 'SF Mono', Menlo, monospace;
            font-size: 1rem;
            font-weight: 600;
        }
        .parties {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .party h3 {
            margin: 0 0 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6b7280;
        }
        .party p { margin: 0.15rem 0; line-height: 1.4; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        thead th {
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            border-bottom: 1px solid #d1d5db;
            padding: 0.5rem 0.4rem;
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.6rem 0.4rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals {
            margin-left: auto;
            width: 320px;
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 0.35rem 0;
            font-size: 0.95rem;
        }
        .totals .row.total {
            border-top: 2px solid #1f2937;
            margin-top: 0.5rem;
            padding-top: 0.6rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .totals .row.paid { color: #047857; }
        .notes {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #4b5563;
            white-space: pre-wrap;
            border-top: 1px dashed #d1d5db;
            padding-top: 1rem;
        }
        .actions {
            max-width: 820px;
            margin: 0 auto 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .actions button, .actions a {
            font: inherit;
            padding: 0.5rem 0.9rem;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #1f2937;
            cursor: pointer;
            text-decoration: none;
        }
        .actions button.primary { background: #1f2937; color: #ffffff; border-color: #1f2937; }
        @media print {
            body { background: #ffffff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; padding: 1.5rem; }
            .actions, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ url()->previous() }}">Close</a>
    </div>

    <div class="sheet">
        <div class="header">
            <div>
                <h1>INVOICE</h1>
                <div class="invoice-number">{{ $sale->invoice_number }}</div>
            </div>
            <div class="invoice-meta">
                <div><strong>Date:</strong> {{ $sale->sale_date?->format('d.m.Y') ?? '—' }}</div>
                @if ($sale->due_date)
                    <div><strong>Due:</strong> {{ $sale->due_date->format('d.m.Y') }}</div>
                @endif
                <div><strong>Status:</strong> {{ ucfirst(str_replace('_',' ', $sale->payment_status)) }}</div>
            </div>
        </div>

        <div class="parties">
            <div class="party">
                <h3>From</h3>
                @if ($settings->logo_path)
                    <p style="margin-bottom:0.6rem;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}"
                             alt="" style="max-height:64px;max-width:220px;display:block;">
                    </p>
                @endif
                <p><strong>{{ $settings->company_name ?? config('app.name', 'ArtDB') }}</strong></p>
                @if ($settings->address_line1)<p>{{ $settings->address_line1 }}</p>@endif
                @if ($settings->address_line2)<p>{{ $settings->address_line2 }}</p>@endif
                @if ($settings->city || $settings->postal_code || $settings->country)
                    <p>{{ collect([$settings->postal_code, $settings->city, $settings->country])->filter()->implode(' ') }}</p>
                @endif
                @if ($settings->email)<p>{{ $settings->email }}</p>@endif
                @if ($settings->phone)<p>{{ $settings->phone }}</p>@endif
                @if ($settings->website)<p>{{ $settings->website }}</p>@endif
                @if (! $settings->email && ! $settings->company_name)<p>{{ config('mail.from.address') }}</p>@endif

                @if ($settings->business_id || $settings->tax_id || $settings->vat_id)
                    <div style="margin-top:0.5rem;font-size:0.82rem;color:#4b5563;">
                        @if ($settings->business_id)<div><strong>IČO:</strong> {{ $settings->business_id }}</div>@endif
                        @if ($settings->tax_id)<div><strong>DIČ:</strong> {{ $settings->tax_id }}</div>@endif
                        @if ($settings->vat_id)<div><strong>IČ DPH:</strong> {{ $settings->vat_id }}</div>@endif
                    </div>
                @endif
            </div>
            <div class="party" style="text-align:right;">
                <h3>Bill to</h3>
                @if ($sale->buyer)
                    <p><strong>{{ $sale->buyer->display_name }}</strong></p>
                    @if ($sale->buyer->organization)
                        <p>{{ $sale->buyer->organization }}</p>
                    @endif
                    @if ($sale->buyer->email)
                        <p>{{ $sale->buyer->email }}</p>
                    @endif
                @else
                    <p style="color:#9ca3af;">— no buyer set —</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:60%;">Artwork</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sale->lineItems as $item)
                    @php
                        $artwork = $item->artwork;
                        $year = $artwork?->year_created;
                        if ($artwork && $artwork->year_created_end && $artwork->year_created_end != $artwork->year_created) {
                            $year = $artwork->year_created.'–'.$artwork->year_created_end;
                        }
                        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                        $dims = $artwork ? collect([
                            $fmt($artwork->height_cm),
                            $fmt($artwork->width_cm),
                            $fmt($artwork->depth_cm),
                        ])->filter()->implode(' × ') : '';
                    @endphp
                    <tr>
                        <td>
                            @if ($artwork)
                                @if ($artwork->artist?->display_name)
                                    <div style="font-weight:600;">{{ $artwork->artist->display_name }}</div>
                                @endif
                                <div><em>{{ $artwork->title }}</em>@if ($year), {{ $year }}@endif</div>
                                @if ($dims !== '')
                                    <div style="color:#6b7280;font-size:0.85em;">{{ $dims }} cm</div>
                                @endif
                            @else
                                {{ $item->description }}
                            @endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ number_format((float) $item->unit_price, 2, ',', ' ') }} {{ $sale->currency }}</td>
                        <td class="num">{{ number_format((float) $item->line_total, 2, ',', ' ') }} {{ $sale->currency }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:1rem;">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>{{ number_format((float) $sale->subtotal, 2, ',', ' ') }} {{ $sale->currency }}</span>
            </div>
            @if ((float) $sale->tax_rate > 0)
                <div class="row">
                    <span>VAT ({{ rtrim(rtrim(number_format((float) $sale->tax_rate, 2, '.', ''), '0'), '.') }} %)</span>
                    <span>{{ number_format((float) $sale->tax_amount, 2, ',', ' ') }} {{ $sale->currency }}</span>
                </div>
            @endif
            @if ((float) $sale->discount_amount > 0)
                <div class="row">
                    <span>Discount</span>
                    <span>− {{ number_format((float) $sale->discount_amount, 2, ',', ' ') }} {{ $sale->currency }}</span>
                </div>
            @endif
            <div class="row total">
                <span>Total</span>
                <span>{{ number_format((float) $sale->total, 2, ',', ' ') }} {{ $sale->currency }}</span>
            </div>
            @if ((float) $sale->paid_amount > 0)
                <div class="row paid">
                    <span>Paid</span>
                    <span>{{ number_format((float) $sale->paid_amount, 2, ',', ' ') }} {{ $sale->currency }}</span>
                </div>
            @endif
        </div>

        @if ($settings->bank_account || $settings->bank_name)
            <div style="margin-top:1.5rem;font-size:0.9rem;border-top:1px dashed #d1d5db;padding-top:1rem;">
                <strong>Payment to:</strong>
                @if ($settings->bank_name){{ $settings->bank_name }}@endif
                @if ($settings->bank_account)
                    <span style="font-family:'SF Mono',Menlo,monospace;">{{ $settings->bank_account }}</span>
                @endif
            </div>
        @endif

        @if ($sale->notes)
            <div class="notes">{{ $sale->notes }}</div>
        @endif

        @if ($settings->footer_notes)
            <div style="margin-top:1.25rem;font-size:0.8rem;color:#6b7280;text-align:center;white-space:pre-wrap;">{{ $settings->footer_notes }}</div>
        @endif
    </div>

    <script>
        // Otvor tlačový dialóg krátko po načítaní (na save-as-PDF aj reálnu tlač).
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));
    </script>
</body>
</html>
