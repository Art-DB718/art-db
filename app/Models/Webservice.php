<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Webservice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'type', 'config', 'is_active',
        'last_sync_at', 'last_sync_status', 'last_sync_message',
        'owner_user_id',
    ];

    protected $casts = [
        'config'       => 'encrypted:array',  // šifruje API keys v DB
        'is_active'    => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }
}
