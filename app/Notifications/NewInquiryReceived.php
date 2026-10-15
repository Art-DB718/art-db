<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Fires when a visitor submits an Inquiry on the public site. The recipient
 * is the artist or gallery user that owns the artwork; they get a Web Push
 * if subscribed. Email stays as the existing primary channel — this adds a
 * second, best-effort surface so a sale lead on the phone is actionable.
 */
class NewInquiryReceived extends Notification
{
    use Queueable;

    public function __construct(public Inquiry $inquiry)
    {
    }

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $sender  = $this->inquiry->sender_display_name;
        $preview = \Illuminate\Support\Str::limit($this->inquiry->message, 120);
        $artwork = $this->inquiry->artwork?->title;
        $title   = $artwork
            ? sprintf('Nová správa o %s', $artwork)
            : 'Nová správa';

        return (new WebPushMessage)
            ->title($title)
            ->body("{$sender}: {$preview}")
            ->icon('/pwa/icon-192.png')
            ->badge('/pwa/icon-192.png')
            ->tag('inquiry-' . $this->inquiry->id)
            ->data([
                'url' => '/admin/inquiries/' . $this->inquiry->id,
            ]);
    }
}
