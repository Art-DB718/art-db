<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        Contact::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'subscribed_to_newsletter' => true,
                'source'                  => 'newsletter',
            ],
        );

        // Ak kontakt existoval bez subscribed_to_newsletter, doplň ho.
        Contact::where('email', $request->input('email'))
            ->update(['subscribed_to_newsletter' => true]);

        return back()->with('newsletter_message', 'Thanks! You’ve been subscribed.');
    }
}
