<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name', 'slug', 'description', 'owner_user_id'];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->name);
            if (empty($m->owner_user_id) && ($u = auth()->user()) && ! $u->isAdmin()) {
                $m->owner_user_id = $u->id;
            }
        });
    }

    public function owner()    { return $this->belongsTo(\App\Models\User::class, 'owner_user_id'); }
    public function artworks() { return $this->hasMany(Artwork::class); }
}
