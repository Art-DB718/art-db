<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Artist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'first_name', 'last_name', 'slug',
        'birth_year', 'death_year', 'birth_place', 'country_id',
        'short_bio', 'biography', 'statement',
        'education', 'previous_exhibitions',
        'website', 'social_links', 'profile_image', 'cover_image',
        'branding_theme', 'is_published', 'is_featured', 'owner_user_id',
    ];

    protected $casts = [
        'social_links'         => 'array',
        'education'            => 'array',
        'previous_exhibitions' => 'array',
        'is_published'         => 'boolean',
        'is_featured'          => 'boolean',
        'birth_year'           => 'integer',
        'death_year'           => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            if (empty($m->slug)) {
                $m->slug = Str::slug(trim(($m->first_name ?? '').' '.($m->last_name ?? '')));
            }
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function country()  { return $this->belongsTo(Country::class); }
    public function artworks() { return $this->hasMany(Artwork::class); }
    public function owner()    { return $this->belongsTo(\App\Models\User::class, 'owner_user_id'); }

    public function galleries()
    {
        return $this->belongsToMany(Gallery::class, 'artist_gallery')
            ->withPivot('represented_since', 'is_primary', 'notes')
            ->withTimestamps();
    }
}
