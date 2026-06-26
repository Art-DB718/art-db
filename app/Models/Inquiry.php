<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory;

    protected $table = 'inquiries';

    protected $fillable = [
        'sender_user_id',
        'recipient_user_id',
        'artwork_id',
        'message',
        'status',
        'read_at',
        'replied_at',
    ];

    protected $casts = [
        'read_at'    => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function sender()    { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_user_id'); }
    public function artwork()   { return $this->belongsTo(Artwork::class); }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
