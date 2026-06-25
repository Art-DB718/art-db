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
    public function artworks() {
        return $this->belongsToMany(Artwork::class)
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('artwork_collection.position');
    }
}
