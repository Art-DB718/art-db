<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'first_name', 'last_name', 'organization',
        'email', 'phone',
        'address_line1', 'address_line2', 'city', 'postal_code', 'country_id',
        'group_id', 'interests', 'notes', 'source', 'last_contact_at',
        'subscribed_to_newsletter', 'owner_user_id',
    ];

    protected $casts = [
        'interests'                => 'array',
        'last_contact_at'          => 'datetime',
        'subscribed_to_newsletter' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uuid ??= (string) Str::uuid());
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        return $name ?: ($this->organization ?? $this->email ?? '—');
    }

    public function country()  { return $this->belongsTo(Country::class); }
    public function group()    { return $this->belongsTo(ContactGroup::class, 'group_id'); }
    public function sales()    { return $this->hasMany(Sale::class, 'buyer_contact_id'); }
}
