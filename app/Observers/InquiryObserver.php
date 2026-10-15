<?php

namespace App\Observers;

use App\Models\Inquiry;
use App\Notifications\NewInquiryReceived;

/**
 * Dispatches a NewInquiryReceived push to the recipient user once the
 * Inquiry row is persisted. Guarded so an Inquiry without a resolved
 * recipient (guest-to-guest edge cases, or legacy rows) does not throw.
 */
class InquiryObserver
{
    public function created(Inquiry $inquiry): void
    {
        $recipient = $inquiry->recipient;

        if ($recipient) {
            $recipient->notify(new NewInquiryReceived($inquiry));
        }
    }
}
