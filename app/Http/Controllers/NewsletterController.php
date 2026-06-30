<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\MailchimpService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function __invoke(Request $request, MailchimpService $mailchimp)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = $request->input('email');

        // Local Contact upsert (CRM record + opt-in flag).
        Contact::firstOrCreate(
            ['email' => $email],
            [
                'subscribed_to_newsletter' => true,
                'source'                   => 'newsletter',
            ],
        );

        Contact::where('email', $email)->update(['subscribed_to_newsletter' => true]);

        // Push to Mailchimp audience (silently no-ops if MAILCHIMP_API_KEY is unset).
        try {
            $mailchimp->subscribe($email);
        } catch (\Throwable $e) {
            logger()->warning('Mailchimp subscribe failed for '.$email.': '.$e->getMessage());
        }

        return back()->with('newsletter_message', 'Thanks! You’ve been subscribed.');
    }
}
