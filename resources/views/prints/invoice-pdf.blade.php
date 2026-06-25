<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 0; font-size: 10pt; line-height: 1.45; }

        .header { width: 100%; border-bottom: 2pt solid #1f2937; padding-bottom: 10pt; margin-bottom: 18pt; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .header h1 { margin: 0; font-size: 22pt; letter-spacing: 0.18em; font-weight: bold; }
        .header .meta { text-align: right; font-size: 9pt; }
        .header .invoice-no { font-family: DejaVu Sans Mono, monospace; font-size: 11pt; font-weight: bold; }

        .parties { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
        .parties td { width: 50%; vertical-align: top; padding-right: 12pt; }
        .parties .label { text-transform: uppercase; letter-spacing: 0.18em; font-size: 8pt; color: #6b7280; margin-bottom: 6pt; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
        table.items th, table.items td { padding: 7pt 4pt; text-align: left; vertical-align: top; border-bottom: 1pt solid #e5e7eb; }
        table.items th { font-size: 8pt; letter-spacing: 0.15em; text-transform: uppercase; color: #6b7280; border-bottom: 2pt solid #1f2937; }
        table.items td.num { text-align: right; white-space: nowrap; }

        .totals { width: 100%; border-collapse: collapse; margin-top: 6pt; }
        .totals td { padding: 4pt 4pt; }
        .totals td.label { text-align: right; color: #6b7280; }
        .totals td.value { text-align: right; width: 110pt; }
        .totals tr.grand td { border-top: 2pt solid #1f2937; font-weight: bold; font-size: 13pt; padding-top: 8pt; }

        .footer-notes { margin-top: 32pt; padding-top: 10pt; border-top: 1pt dashed #d1d5db; font-size: 9pt; color: #6b7280; }
        .bank-info { margin-top: 12pt; font-size: 9pt; }
        .bank-info span { color: #6b7280; margin-right: 6pt; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width:60%;">
                    <h1>INVOICE</h1>
                    <div style="margin-top:4pt;font-size:9pt;color:#6b7280;">{{ $settings->company_name ?? config('app.name') }}</div>
                </td>
                <td class="meta">
                    <div class="invoice-no">{{ $sale->invoice_number }}</div>
                    @if ($sale->sale_date)<div>Issued: {{ $sale->sale_date->format('d.m.Y') }}</div>@endif
                    @if ($sale->due_date)<div>Due: {{ $sale->due_date->format('d.m.Y') }}</div>@endif
                </td>
            </tr>
        </table>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="label">From</div>
                <strong>{{ $settings->company_name }}</strong><br>
                @if ($settings->address_line1){{ $settings->address_line1 }}<br>@endif
                @if ($settings->address_line2){{ $settings->address_line2 }}<br>@endif
                @if ($settings->postal_code || $settings->city){{ trim(($settings->postal_code ?? '').' '.($settings->city ?? '')) }}<br>@endif
                @if ($settings->country){{ $settings->country }}<br>@endif
                <br>
                @if ($settings->business_id)IČO: {{ $settings->business_id }}<br>@endif
                @if ($settings->tax_id)DIČ: {{ $settings->tax_id }}<br>@endif
                @if ($settings->vat_id)IČ DPH: {{ $settings->vat_id }}<br>@endif
                @if ($settings->email)<br>{{ $settings->email }}@endif
            </td>
            <td>
                <div class="label">Bill to</div>
                @if ($sale->buyer)
                    <strong>{{ $sale->buyer->display_name }}</strong><br>
                    @if ($sale->buyer->organization){{ $sale->buyer->organization }}<br>@endif
                    @if ($sale->buyer->address_line1){{ $sale->buyer->address_line1 }}<br>@endif
                    @if ($sale->buyer->city){{ trim(($sale->buyer->postal_code ?? '').' '.($sale->buyer->city ?? '')) }}<br>@endif
                    @if ($sale->buyer->country){{ $sale->buyer->country->name ?? $sale->buyer->country }}<br>@endif
                    @if ($sale->buyer->email)<br>{{ $sale->buyer->email }}@endif
                @else
                    <em style="color:#9ca3af;">No buyer recorded</em>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit price</th>
                <th class="num">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->lineItems as $line)
                <tr>
                    <td>
                        @if ($line->artwork)
                            <strong>{{ $line->artwork->artist?->display_name ?? '—' }}</strong><br>
                            <em>{{ $line->artwork->title }}</em>@if ($line->artwork->year_created), {{ $line->artwork->year_created }}@endif
                            @if ($line->artwork->medium?->name || $line->artwork->height_cm)
                                <br><span style="color:#6b7280;font-size:8pt;">
                                    @if ($line->artwork->medium?->name){{ $line->artwork->medium->name }}@endif
                                    @php
                                        $fmt = fn ($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                                        $dims = collect([$fmt($line->artwork->height_cm), $fmt($line->artwork->width_cm), $fmt($line->artwork->depth_cm)])->filter()->implode(' × ');
                                    @endphp
                                    @if ($dims) · {{ $dims }} cm @endif
                                </span>
                            @endif
                        @else
                            {{ $line->description ?: '—' }}
                        @endif
                    </td>
                    <td class="num">{{ $line->quantity ?? 1 }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
                    <td class="num">{{ number_format((float) $line->line_total, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format((float) $sale->subtotal, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
        </tr>
        @if ((float) $sale->discount_amount > 0)
        <tr>
            <td class="label">Discount</td>
            <td class="value">−{{ number_format((float) $sale->discount_amount, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
        </tr>
        @endif
        @if ((float) $sale->tax_rate > 0)
        <tr>
            <td class="label">VAT ({{ rtrim(rtrim(number_format((float) $sale->tax_rate, 2, '.', ''), '0'), '.') }} %)</td>
            <td class="value">{{ number_format((float) $sale->tax_amount, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
        </tr>
        @endif
        <tr class="grand">
            <td class="label">Total</td>
            <td class="value">{{ number_format((float) $sale->total, 2, '.', ' ') }} {{ $sale->currency ?? 'EUR' }}</td>
        </tr>
    </table>

    @if ($settings->bank_account)
        <div class="bank-info">
            <span>Bank:</span> {{ $settings->bank_name }} ·
            <span>IBAN:</span> <span style="font-family:DejaVu Sans Mono,monospace;">{{ $settings->bank_account }}</span> ·
            <span>Variable symbol:</span> {{ preg_replace('/\D/', '', $sale->invoice_number) ?: $sale->id }}
        </div>
    @endif

    @if ($settings->footer_notes)
        <div class="footer-notes">{{ $settings->footer_notes }}</div>
    @endif
</body>
</html>
