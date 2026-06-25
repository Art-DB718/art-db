<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Medium extends Model
{
    use HasFactory;

    // Laravel pluralizuje "Medium" → "media"; migrácia vytvára "mediums".
    protected $table = 'mediums';

    protected $fillable = ['uuid', 'parent_id', 'name', 'slug', 'description', 'position'];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
        });
    }

    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function artworks() { return $this->hasMany(Artwork::class); }
}
