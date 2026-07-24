<?php

namespace App\Mail;

use App\Models\ArtistClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistClaimRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ArtistClaim $claim)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ownership request was declined',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.artist-claim-rejected',
            with: [
                'artist'   => $this->claim->artist,
                'reason'   => $this->claim->notes,
                'adminUrl' => url('/admin/artists/create'),
                'appName'  => config('app.name', 'Art DB'),
            ],
        );
    }
}
