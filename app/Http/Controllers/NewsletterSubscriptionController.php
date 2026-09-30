<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Public one-click subscribe / unsubscribe from an email footer.
 * URLs are Laravel signed routes so people can't guess other contacts'
 * links — a tampered signature returns 403.
 */
class NewsletterSubscriptionController extends Controller
{
    public function __invoke(Request $request, string $action, int $contact): \Illuminate\View\View
    {
        if (! in_array($action, ['subscribe', 'unsubscribe'], true)) {
            throw new HttpException(404, 'Unknown action');
        }
        if (! $request->hasValidSignature()) {
            throw new HttpException(403, 'Invalid or expired link');
        }

        $record = Contact::findOrFail($contact);

        $wanted = $action === 'subscribe';

        if ($record->subscribed_to_newsletter !== $wanted) {
            $record->subscribed_to_newsletter = $wanted;
            $record->newsletter_status_updated_at = now();
            $record->save();
        } else {
            // Still stamp the timestamp so Kat sees they used the link.
            $record->newsletter_status_updated_at = now();
            $record->save();
        }

        return view('newsletter.confirmation', [
            'contact' => $record,
            'action'  => $action,
        ]);
    }
}
