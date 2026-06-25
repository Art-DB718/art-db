<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'artwork_id',
        'restoration_date',
        'restoration_returned_at',
        'restoration_notes',
        'restoration_price',
        'restorer_name',
        'restorer_email',
        'restorer_phone',
        'documents',
        'photos',
        'position',
    ];

    protected $casts = [
        'restoration_date'        => 'date',
        'restoration_returned_at' => 'date',
        'restoration_price'       => 'decimal:2',
        'documents'               => 'array',
        'photos'                  => 'array',
        'position'                => 'integer',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    /** in_progress | returned */
    public function getStatusAttribute(): string
    {
        return $this->restoration_returned_at ? 'returned' : 'in_progress';
    }
}
