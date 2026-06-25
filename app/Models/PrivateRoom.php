<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PrivateRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'title', 'slug', 'token',
        'recipient_contact_id', 'recipient_name', 'recipient_email',
        'welcome_message', 'cover_image', 'expires_at',
        'show_prices', 'allow_inquiry', 'sort_strategy', 'discount_percent',
        'view_count', 'last_viewed_at', 'sent_at', 'owner_user_id',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'last_viewed_at'   => 'datetime',
        'sent_at'          => 'datetime',
        'show_prices'      => 'boolean',
        'allow_inquiry'    => 'boolean',
        'view_count'       => 'integer',
        'discount_percent' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid  ??= (string) Str::uuid();
            $m->token ??= Str::random(40);
            $m->slug  ??= Str::slug($m->title.'-'.substr($m->token, 0, 6));
        });
    }

    public function recipient() { return $this->belongsTo(Contact::class, 'recipient_contact_id'); }

    /** Príjemcovia (M2M) — komu sa private room posiela. */
    public function recipients()
    {
        return $this->belongsToMany(Contact::class, 'contact_private_room')
            ->withPivot('status', 'sent_at')
            ->withTimestamps();
    }

    public function artworks()  {
        return $this->belongsToMany(Artwork::class, 'private_room_artwork')
            ->withPivot('display_price','currency','position','private_note')
            ->withTimestamps()
            ->orderBy('private_room_artwork.position');
    }

    public function publicUrl(): string
    {
        return url('/private-room/'.$this->token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
