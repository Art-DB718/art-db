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
        'subscribed_to_newsletter', 'newsletter_status_updated_at', 'owner_user_id',
    ];

    protected $casts = [
        'interests'                    => 'array',
        'last_contact_at'              => 'datetime',
        'subscribed_to_newsletter'     => 'boolean',
        'newsletter_status_updated_at' => 'datetime',
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

    /** Many-to-many groups (a contact can be in several groups). */
    public function groups()
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_contact_group')->withTimestamps();
    }

    /** Artists this contact is interested in / follows. */
    public function interestedArtists()
    {
        return $this->belongsToMany(Artist::class, 'contact_artist_interest')->withTimestamps();
    }

    /**
     * Line items from every sale where this contact is the buyer — used to
     * count how many individual artworks the contact has purchased (a single
     * sale can span several works).
     */
    public function purchasedArtworkLines()
    {
        return $this->hasManyThrough(
            SaleLineItem::class,
            Sale::class,
            'buyer_contact_id',   // FK on sales
            'sale_id',            // FK on sale_line_items
            'id',                 // local key on contacts
            'id',                 // local key on sales
        );
    }
}
