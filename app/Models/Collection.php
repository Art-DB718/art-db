<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'parent_id', 'title', 'slug', 'description',
        'cover_image', 'is_public', 'position', 'owner_user_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->title);
        });
    }

    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function owner()    { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function artworks() {
        return $this->belongsToMany(Artwork::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('artwork_collection.position');
    }

    /** Primary-image paths of artworks in this collection — for stacked preview column. */
    public function getArtworkPreviewsAttribute(): array
    {
        return $this->artworks()
            ->whereNotNull('primary_image')
            ->limit(20)
            ->pluck('primary_image')
            ->all();
    }

    /** Sum of artwork prices in this collection. */
    public function getArtworksTotalValueAttribute(): ?float
    {
        $sum = (float) $this->artworks()->sum('price');
        return $sum > 0 ? $sum : null;
    }

    /** Count of artworks with no price (or price_on_request) — useful complement to total. */
    public function getArtworksWithoutPriceCountAttribute(): int
    {
        return $this->artworks()
            ->where(fn ($q) => $q->whereNull('price')->orWhere('price', 0)->orWhere('price_on_request', true))
            ->count();
    }
}
