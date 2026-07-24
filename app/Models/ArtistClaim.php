<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtistClaim extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'artist_id',
        'claimant_user_id',
        'status',
        'responded_at',
        'responded_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function artist()      { return $this->belongsTo(Artist::class); }
    public function claimant()    { return $this->belongsTo(User::class, 'claimant_user_id'); }
    public function respondedBy() { return $this->belongsTo(User::class, 'responded_by_user_id'); }

    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeRejected($q) { return $q->where('status', self::STATUS_REJECTED); }

    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isRejected(): bool { return $this->status === self::STATUS_REJECTED; }

    /**
     * Approve the claim — transfer Artist ownership to the claimant and,
     * when the previous owner ran a gallery, keep the artist attached to
     * their roster via the artist_gallery pivot so nothing disappears from
     * the gallery's public page.
     */
    public function approve(User $responder): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($responder) {
            $artist       = $this->artist;
            $previousUser = $artist->owner;

            if ($previousUser?->isGallery() && $previousUser->gallery) {
                // Keep the represented-artists pivot link so the gallery
                // still shows the artist even after ownership transfer.
                $previousUser->gallery->artists()->syncWithoutDetaching([$artist->id]);
            }

            $artist->forceFill(['owner_user_id' => $this->claimant_user_id])->save();

            $this->forceFill([
                'status'               => self::STATUS_APPROVED,
                'responded_at'         => now(),
                'responded_by_user_id' => $responder->id,
            ])->save();
        });
    }

    public function reject(User $responder, ?string $reason = null): void
    {
        $this->forceFill([
            'status'               => self::STATUS_REJECTED,
            'responded_at'         => now(),
            'responded_by_user_id' => $responder->id,
            'notes'                => $reason ?: $this->notes,
        ])->save();
    }
}
