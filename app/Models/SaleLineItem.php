<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'artwork_id', 'description',
        'quantity', 'unit_price', 'line_total', 'position',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($m) {
            $m->line_total = (float) $m->quantity * (float) $m->unit_price;
        });
    }

    public function sale()    { return $this->belongsTo(Sale::class); }
    public function artwork() { return $this->belongsTo(Artwork::class); }
}
