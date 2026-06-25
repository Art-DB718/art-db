<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'invoice_number', 'buyer_contact_id',
        'sale_date', 'due_date',
        'payment_status', 'payment_method', 'currency',
        'subtotal', 'tax_rate', 'tax_amount', 'discount_amount', 'total', 'paid_amount',
        'notes', 'internal_notes', 'billing_address', 'shipping_address',
        'owner_user_id',
    ];

    protected $casts = [
        'sale_date'        => 'date',
        'due_date'         => 'date',
        'subtotal'         => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'total'            => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'billing_address'  => 'array',
        'shipping_address' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            if (empty($m->invoice_number)) {
                $year = now()->format('Y');
                $next = static::whereYear('created_at', $year)->count() + 1;
                $m->invoice_number = sprintf('INV-%s-%04d', $year, $next);
            }
        });
    }

    public function buyer()     { return $this->belongsTo(Contact::class, 'buyer_contact_id'); }
    public function lineItems() { return $this->hasMany(SaleLineItem::class)->orderBy('position'); }

    /**
     * Prepočíta subtotal (suma riadkov), tax_amount a total a uloží ich.
     * Volá sa po zmene line items aj po editovaní sadzby DPH / zľavy.
     */
    public function recalculateTotals(): void
    {
        $subtotal  = (float) $this->lineItems()->sum('line_total');
        $taxAmount = round($subtotal * ((float) $this->tax_rate / 100), 2);

        $this->subtotal   = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total      = round($subtotal + $taxAmount - (float) $this->discount_amount, 2);
        $this->saveQuietly();
    }
}
