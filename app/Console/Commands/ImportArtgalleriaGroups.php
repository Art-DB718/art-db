<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Import contact-group memberships scraped from artgalleria.com.
 *
 * JSON shape (produced by the Chrome scraper):
 *   [
 *     { "gid": 12861, "name": "Buyers", "contacts": [
 *         {"first":"…","last":"…","company":"…","email":"…","email2":"…","email3":"…"},
 *         …
 *     ]},
 *     …
 *   ]
 *
 * For each group, findOrCreate ContactGroup by name (owner-agnostic — groups
 * are shared per install just like the existing table), then match each
 * contact to an art-db Contact (owner-scoped by email, falling back to
 * name+company) and attach via the many-to-many pivot.
 *
 * Usage:
 *   php artisan import:artgalleria-groups path/to/artgalleria-groups.json --user=<gallery_user_id>
 */
class ImportArtgalleriaGroups extends Command
{
    protected $signature = 'import:artgalleria-groups
        {json : Path to the scraped JSON file}
        {--user= : Owner Gallery user id (defaults to info@rfg.sk)}
        {--dry-run : Print plan without writing to DB}';

    protected $description = 'Import contact-group memberships from artgalleria into art-db';

    public function handle(): int
    {
        $path = $this->argument('json');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $userId = $this->option('user');
        $user = $userId
            ? User::find((int) $userId)
            : User::where('email', 'info@rfg.sk')->first();

        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error('Owner user not found or not a Gallery role.');

            return self::FAILURE;
        }

        $groups = json_decode(file_get_contents($path), true);
        if (! is_array($groups)) {
            $this->error('JSON is not a top-level array.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s %d groups for user #%d …',
            $dryRun ? '[DRY RUN]' : 'Importing',
            count($groups),
            $user->id,
        ));

        $attachedTotal = 0;
        $missingTotal  = 0;

        foreach ($groups as $g) {
            $groupName = trim((string) ($g['name'] ?? ''));
            if ($groupName === '') {
                continue;
            }

            $group = ContactGroup::firstOrCreate(['name' => $groupName]);
            if ($dryRun) {
                $this->line("  · would ensure group: {$groupName}");
            }

            $attached = 0;
            $missing  = [];
            foreach ($g['contacts'] ?? [] as $c) {
                $contact = $this->findContact($c, $user);
                if (! $contact) {
                    $missing[] = trim(($c['first'] ?? '').' '.($c['last'] ?? '').' <'.($c['email'] ?? '').'>');
                    continue;
                }
                if (! $dryRun && ! $contact->groups()->where('contact_groups.id', $group->id)->exists()) {
                    $contact->groups()->attach($group->id);
                }
                $attached++;
            }

            $attachedTotal += $attached;
            $missingTotal  += count($missing);

            $this->line(sprintf(
                '  %-12s  attached=%d  missing=%d',
                $groupName,
                $attached,
                count($missing),
            ));
            foreach (array_slice($missing, 0, 5) as $m) {
                $this->line("     - {$m}");
            }
            if (count($missing) > 5) {
                $this->line(sprintf('     … and %d more', count($missing) - 5));
            }
        }

        $this->newLine();
        $this->info(sprintf('Done: attached=%d, missing=%d', $attachedTotal, $missingTotal));

        return self::SUCCESS;
    }

    /**
     * Match an artgalleria contact to an art-db Contact. Try Email, Email 2,
     * Email 3 first (owner-scoped), then fall back to name+company match.
     */
    protected function findContact(array $c, User $user): ?Contact
    {
        $emails = array_filter([
            $c['email']  ?? null,
            $c['email2'] ?? null,
            $c['email3'] ?? null,
        ], fn ($e) => filled($e));

        foreach ($emails as $email) {
            $email = strtolower(trim($email));
            $contact = Contact::where('owner_user_id', $user->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($contact) {
                return $contact;
            }
        }

        $first = trim((string) ($c['first']   ?? ''));
        $last  = trim((string) ($c['last']    ?? ''));
        $org   = trim((string) ($c['company'] ?? ''));

        if ($first === '' && $last === '' && $org === '') {
            return null;
        }

        return Contact::where('owner_user_id', $user->id)
            ->when($first !== '', fn ($q) => $q->where('first_name', $first))
            ->when($last  !== '', fn ($q) => $q->where('last_name', $last))
            ->when($org   !== '', fn ($q) => $q->where('organization', $org))
            ->first();
    }
}
