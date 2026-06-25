<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Gallery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'galleries';

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'logo', 'cover_image',
        'address_line1', 'address_line2', 'city', 'postal_code', 'country_id',
        'website', 'email', 'phone',
        'owner_user_id', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $g) {
            $g->uuid ??= (string) Str::uuid();
            if (empty($g->slug)) {
                $g->slug = Str::slug($g->name ?? 'gallery').'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function country() { return $this->belongsTo(Country::class); }
    public function owner()   { return $this->belongsTo(User::class, 'owner_user_id'); }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'artist_gallery')
            ->withPivot('represented_since', 'is_primary', 'notes')
            ->withTimestamps();
    }
}
