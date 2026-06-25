<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Webservice;
use DrewM\MailChimp\MailChimp;
use Illuminate\Support\Facades\Log;

class MailchimpService
{
    protected ?MailChimp $client = null;
    protected ?string $listId = null;
    protected bool $dryRun = false;

    public function __construct(?Webservice $service = null)
    {
        // Najprv DB Webservice (admin can configure cez Filament),
        // fallback na env.
        $service ??= Webservice::query()
            ->where('type', 'mailchimp')
            ->where('is_active', true)
            ->first();

        $apiKey       = $service?->config['api_key'] ?? env('MAILCHIMP_API_KEY');
        $this->listId = $service?->config['list_id'] ?? env('MAILCHIMP_LIST_ID');

        if (! $apiKey) {
            $this->dryRun = true;
            Log::info('MailchimpService: dry-run mode (no API key configured).');
            return;
        }

        try {
            $this->client = new MailChimp($apiKey);
        } catch (\Throwable $e) {
            Log::warning('MailchimpService boot failed: '.$e->getMessage());
            $this->dryRun = true;
        }
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Sync (upsert) all subscribed contacts to the configured Mailchimp audience.
     * Returns count of synced records.
     */
    public function syncSubscribedContacts(): array
    {
        $contacts = Contact::query()
            ->where('subscribed_to_newsletter', true)
            ->whereNotNull('email')
            ->get();

        $synced = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($contacts as $contact) {
            $payload = [
                'email_address' => $contact->email,
                'status_if_new' => 'subscribed',
                'merge_fields'  => array_filter([
                    'FNAME' => $contact->first_name,
                    'LNAME' => $contact->last_name,
                ]),
            ];

            if ($this->dryRun || ! $this->client || ! $this->listId) {
                $skipped++;
                Log::info('Mailchimp (dry-run): would upsert '.$contact->email);
                continue;
            }

            $hash = md5(strtolower($contact->email));
            $result = $this->client->put("lists/{$this->listId}/members/{$hash}", $payload);

            if ($this->client->success()) {
                $synced++;
            } else {
                $errors[] = ['email' => $contact->email, 'error' => $this->client->getLastError()];
            }
        }

        return [
            'total'   => $contacts->count(),
            'synced'  => $synced,
            'skipped' => $skipped,
            'errors'  => $errors,
            'dry_run' => $this->dryRun,
        ];
    }
}
