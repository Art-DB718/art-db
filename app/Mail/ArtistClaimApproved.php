<?php

namespace App\Mail;

use App\Models\ArtistClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistClaimApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ArtistClaim $claim)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your artist profile is now yours',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.artist-claim-approved',
            with: [
                'artist'    => $this->claim->artist,
                'adminUrl'  => url('/admin/artists'),
                'appName'   => config('app.name', 'Art DB'),
            ],
        );
    }
}
