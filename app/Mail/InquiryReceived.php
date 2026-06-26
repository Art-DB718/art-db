<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class InquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        $artwork = $this->inquiry->artwork;
        $sender  = $this->inquiry->sender;

        return new Envelope(
            subject: 'New inquiry about: '.($artwork?->title ?? 'an artwork'),
            replyTo: $sender?->email
                ? [new Address($sender->email, $sender->name ?? '')]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.inquiry-received',
            with: [
                'inquiry'      => $this->inquiry,
                'artwork'      => $this->inquiry->artwork,
                'sender'       => $this->inquiry->sender,
                'inboxUrl'     => url('/admin/inquiries/'.$this->inquiry->id.'/edit'),
            ],
        );
    }
}
