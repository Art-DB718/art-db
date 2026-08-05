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
                $base = Str::slug(trim(($m->first_name ?? '').' '.($m->last_name ?? ''))) ?: 'artist';
                // Uniqueness safeguard — two artists can share a name (jr / sr,
                // homonyms, misspellings). Append a random suffix only on
                // collision so the first artist keeps the clean SEO slug.
                $m->slug = static::query()->where('slug', $base)->exists()
                    ? $base.'-'.Str::lower(Str::random(4))
                    : $base;
            }
            // Auto-assign owner for non-admin creators so an Artist created
            // inline from an Artwork form (Select::createOptionForm) still
            // lands in the tenant's own /admin/artists list.
            if (empty($m->owner_user_id) && ($u = auth()->user()) && ! $u->isAdmin()) {
                $m->owner_user_id = $u->id;
            }
        });

        static::saved(function (self $m) {
            if ($m->wasChanged('profile_image') || $m->wasChanged('cover_image')) {
                app(\App\Services\StorageAccountant::class)->syncFromModel($m);
            }
        });

        static::deleted(function (self $m) {
            app(\App\Services\StorageAccountant::class)->syncFromModel($m);
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
