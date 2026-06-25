<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected ?string $from = null,
        protected ?string $to = null,
        protected ?string $paymentStatus = null,
    ) {}

    public function query()
    {
        $q = Sale::query()->with(['buyer', 'lineItems.artwork.artist']);

        if ($this->from) $q->whereDate('sale_date', '>=', $this->from);
        if ($this->to)   $q->whereDate('sale_date', '<=', $this->to);
        if ($this->paymentStatus) $q->where('payment_status', $this->paymentStatus);

        return $q->orderByDesc('sale_date');
    }

    public function headings(): array
    {
        return [
            'Invoice #', 'Sold at', 'Due at',
            'Buyer', 'Buyer email',
            'Items',
            'Subtotal', 'Discount', 'Tax rate %', 'Tax amount', 'Total', 'Currency',
            'Payment status', 'Paid amount',
            'Created',
        ];
    }

    public function map($s): array
    {
        $itemSummary = $s->lineItems->map(function ($line) {
            $aw = $line->artwork;
            return $aw
                ? trim(($aw->artist?->display_name ?? '').' — '.$aw->title.($aw->year_created ? ' ('.$aw->year_created.')' : ''))
                : ($line->description ?: '');
        })->filter()->implode('; ');

        return [
            $s->invoice_number,
            $s->sale_date?->format('Y-m-d'),
            $s->due_date?->format('Y-m-d'),
            $s->buyer?->display_name,
            $s->buyer?->email,
            $itemSummary,
            $s->subtotal,
            $s->discount_amount,
            $s->tax_rate,
            $s->tax_amount,
            $s->total,
            $s->currency ?? 'EUR',
            $s->payment_status,
            $s->paid_amount,
            $s->created_at?->format('Y-m-d'),
        ];
    }
}
