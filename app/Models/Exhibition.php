<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exhibition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'title', 'slug', 'type', 'venue', 'location_id',
        'start_date', 'end_date', 'opening_at', 'curator',
        'description', 'press_release', 'invitation_message',
        'poster_image', 'gallery_images', 'documents',
        'status', 'is_published', 'owner_user_id',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'documents'      => 'array',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'opening_at'     => 'datetime',
        'is_published'   => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
            $m->slug ??= Str::slug($m->title.'-'.Str::random(4));
        });
    }

    public function location() { return $this->belongsTo(Location::class); }
    public function artworks() {
        return $this->belongsToMany(Artwork::class)
            ->withPivot('position','was_sold','notes')
            ->withTimestamps()
            ->orderBy('artwork_exhibition.position');
    }

    /** Pozvaní hostia z contact listu (väzba cez contact_exhibition). */
    public function invitedContacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_exhibition')
            ->withPivot('status', 'sent_at')
            ->withTimestamps();
    }
}
