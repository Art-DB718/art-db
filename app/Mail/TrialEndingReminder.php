<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public int $daysLeft)
    {
    }

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->daysLeft <= 1 => 'Your '.config('app.name').' trial ends tomorrow',
            $this->daysLeft <= 3 => $this->daysLeft.' days left in your '.config('app.name').' trial',
            default              => 'Your '.config('app.name').' trial ends in '.$this->daysLeft.' days',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.trial-ending-reminder',
            with: [
                'user'         => $this->user,
                'daysLeft'     => $this->daysLeft,
                'billingUrl'   => url('/admin/billing'),
                'appName'      => config('app.name', 'Art DB'),
            ],
        );
    }
}
