<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'name', 'iso_alpha2', 'iso_alpha3'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid ??= (string) \Illuminate\Support\Str::uuid());
    }

    public function artists()  { return $this->hasMany(Artist::class); }
    public function contacts() { return $this->hasMany(Contact::class); }
}
