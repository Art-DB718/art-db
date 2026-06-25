<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'type',
        'address_line1', 'address_line2', 'city', 'postal_code', 'country_id',
        'notes', 'owner_user_id',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function country()   { return $this->belongsTo(Country::class); }
    public function artworks()  { return $this->hasMany(Artwork::class); }
    public function exhibitions() { return $this->hasMany(Exhibition::class); }
}
