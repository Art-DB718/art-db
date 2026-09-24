<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\Country;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Import artgalleria contact list into art-db Contact model.
 * Matches by (owner_user_id, email) so re-runs update in place.
 */
class ImportArtgalleriaContacts extends Command
{
    protected $signature = 'import:artgalleria-contacts
        {json : Path to scraped contacts JSON}
        {--user= : Owner user id}
        {--dry-run}';

    protected $description = 'Import artgalleria contacts into art-db';

    public function handle(): int
    {
        $path = $this->argument('json');
        if (! is_file($path)) { $this->error("File not found: $path"); return 1; }
        $userId = (int) $this->option('user');
        $user = User::find($userId);
        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error("User $userId not found or not Gallery"); return 1;
        }
        $items = json_decode(file_get_contents($path), true);
        $dryRun = (bool) $this->option('dry-run');

        $created = $updated = $skipped = 0;
        foreach ($items as $i => $it) {
            $first = trim((string) ($it['firstname'] ?? ''));
            $last  = trim((string) ($it['lastname']  ?? ''));
            $email = trim(strtolower((string) ($it['email'] ?? '')));
            $company = trim((string) ($it['company'] ?? ''));

            if ($first === '' && $last === '' && $company === '' && $email === '') { $skipped++; continue; }

            $country = null;
            if (! empty($it['country_code'])) {
                $c = Country::whereRaw('LOWER(name) = ?', [strtolower($it['country_code'])])->first();
                $country = $c?->id;
            }

            $attrs = array_filter([
                'first_name'   => $first ?: null,
                'last_name'    => $last ?: ($company ?: 'Unknown'),
                'organization' => $company ?: null,
                'email'        => $email ?: null,
                'phone'        => trim((string) ($it['phone'] ?? '')) ?: null,
                'address_line1'=> trim((string) ($it['address_1'] ?? '')) ?: null,
                'address_line2'=> trim((string) ($it['address_2'] ?? '')) ?: null,
                'city'         => trim((string) ($it['city'] ?? '')) ?: null,
                'postal_code'  => trim((string) ($it['postcode'] ?? '')) ?: null,
                'country_id'   => $country,
                'source'       => 'artgalleria',
                'owner_user_id'=> $user->id,
            ], fn($v) => $v !== null && $v !== '');

            // Dedupe: prefer email; fall back to name+organization to avoid mass-duplicating no-email records.
            $existing = null;
            if ($email) {
                $existing = Contact::where('owner_user_id', $user->id)->where('email', $email)->first();
            }
            if (! $existing && ! $email) {
                $existing = Contact::where('owner_user_id', $user->id)
                    ->where('first_name', $first ?: null)
                    ->where('last_name',  $last  ?: null)
                    ->where('organization', $company ?: null)
                    ->first();
            }

            if ($dryRun) {
                $this->line(sprintf('  %4d. %s  %s %s %s', $i+1,
                    $existing ? 'UPDATE' : 'CREATE',
                    $first, $last, $email ? "<$email>" : ''));
                $existing ? $updated++ : $created++;
                continue;
            }

            if ($existing) { $existing->update($attrs); $updated++; }
            else           { Contact::create(array_merge(['uuid' => Str::uuid()], $attrs)); $created++; }
        }

        $this->info(sprintf('Done: %d created, %d updated, %d skipped', $created, $updated, $skipped));
        return 0;
    }
}
