<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArtworkStatus extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name', 'slug', 'color', 'is_public', 'counts_as_available', 'position'];

    protected $casts = [
        'is_public'           => 'boolean',
        'counts_as_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
        });
    }

    public function artworks() { return $this->hasMany(Artwork::class, 'status_id'); }
}
