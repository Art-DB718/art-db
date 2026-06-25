<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'onboarded_at',
        'institution_name',
        'institution_city',
        'institution_country',
        'institution_website',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at'      => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isGallery(): bool
    {
        return $this->role === UserRole::Gallery;
    }

    public function isArtist(): bool
    {
        return $this->role === UserRole::Artist;
    }

    public function isCollector(): bool
    {
        return $this->role === UserRole::Collector;
    }

    /** Diela, ktoré user vlastní (artists, galleries — owner_user_id). */
    public function artworks()
    {
        return $this->hasMany(Artwork::class, 'owner_user_id');
    }

    /** Artist profile linked to this user (only relevant for role=artist). */
    public function artistProfile()
    {
        return $this->hasOne(Artist::class, 'owner_user_id');
    }

    /** Artworks this user has hearted. */
    public function likedArtworks()
    {
        return $this->belongsToMany(Artwork::class, 'artwork_likes')->withTimestamps();
    }

    /** Artworks saved to this user's personal collection. */
    public function savedArtworks()
    {
        return $this->belongsToMany(Artwork::class, 'artwork_saves')
            ->withPivot('private_note')
            ->withTimestamps();
    }

    /** Filament panel gate — Collectors are blocked from /admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role?->canAccessFilament() ?? false;
    }
}
