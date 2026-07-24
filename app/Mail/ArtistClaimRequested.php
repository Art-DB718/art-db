<?php

namespace App\Mail;

use App\Models\ArtistClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistClaimRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ArtistClaim $claim)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ownership request: '.($this->claim->artist->display_name ?? 'an artist profile'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.artist-claim-requested',
            with: [
                'claim'     => $this->claim,
                'artist'    => $this->claim->artist,
                'claimant'  => $this->claim->claimant,
                'reviewUrl' => url('/admin/artist-claims'),
                'appName'   => config('app.name', 'Art DB'),
            ],
        );
    }
}
