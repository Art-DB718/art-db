<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Artist;
use App\Models\Country;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Enrich already-imported RFG artists with details scraped from
 * artgalleria's /app/artist_profiles/{id}/edit pages: biography,
 * previous exhibitions, awards, website, social links, country.
 *
 * Matches by (first_name, last_name) against Artists owned by --user.
 * Existing values are preserved unless --overwrite is set.
 */
class ImportArtgalleriaArtists extends Command
{
    protected $signature = 'import:artgalleria-artists
        {json : Path to scraped artists JSON}
        {--user= : Owner user id (Gallery role)}
        {--dry-run : Print plan without writing DB}
        {--overwrite : Overwrite existing non-empty fields}';

    protected $description = 'Enrich RFG Artists with details scraped from artgalleria artist profiles';

    public function handle(): int
    {
        $path = $this->argument('json');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }
        $userId = (int) $this->option('user');
        $user = User::find($userId);
        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error("User id {$userId} not found or not a Gallery role.");
            return self::FAILURE;
        }

        $items = json_decode(file_get_contents($path), true);
        if (! is_array($items)) {
            $this->error('JSON is not a top-level array.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');

        $this->info(sprintf('%s %d artist profiles into user #%d …',
            $dryRun ? '[DRY RUN]' : 'Enriching', count($items), $user->id,
        ));

        $updated = $skipped = $notMatched = 0;

        foreach ($items as $i => $item) {
            $first = trim((string) ($item['firstname'] ?? ''));
            $last  = trim((string) ($item['lastname']  ?? ''));

            if ($first === '' && $last === '') {
                $skipped++;
                continue;
            }
            if (in_array($first, ['_'], true)) $first = '';
            if (in_array($last,  ['_'], true)) $last  = '';

            $artist = Artist::where('owner_user_id', $user->id)
                ->where('first_name', $first)
                ->where('last_name',  $last)
                ->first();

            if (! $artist) {
                $notMatched++;
                $this->line(sprintf('  %3d. NO-MATCH  %s %s', $i + 1, $first, $last));
                continue;
            }

            $attrs = $this->mapArtistAttributes($item, $artist, $overwrite);
            if (empty($attrs)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  %3d. WOULD UPDATE %s %s → %s',
                    $i + 1, $first, $last, implode(', ', array_keys($attrs))));
            } else {
                $artist->update($attrs);
            }
            $updated++;
        }

        $this->newLine();
        $this->info(sprintf('Done: %d %s, %d skipped (empty/no new data), %d not matched',
            $updated, $dryRun ? 'would update' : 'updated', $skipped, $notMatched));

        return self::SUCCESS;
    }

    /**
     * Map artgalleria artist_profile fields to art-db Artist columns.
     * Skips writing over existing non-empty values unless --overwrite.
     */
    protected function mapArtistAttributes(array $item, Artist $artist, bool $overwrite): array
    {
        $attrs = [];
        $set = function (string $key, $value) use (&$attrs, $artist, $overwrite) {
            if ($value === null || $value === '' || $value === []) return;
            if (! $overwrite && filled($artist->getAttribute($key))) return;
            $attrs[$key] = $value;
        };

        // Biography — glue awards on if we have them; artgalleria doesn't
        // have a separate awards column in our Artist model.
        $bio = trim((string) ($item['biography'] ?? ''));
        $awards = trim((string) ($item['awards'] ?? ''));
        if ($awards !== '') {
            $bio = $bio ? $bio."\n\n---\n\nAwards:\n".$awards : "Awards:\n".$awards;
        }
        $set('biography', $bio ?: null);

        $set('statement', trim((string) ($item['artist_statement'] ?? '')) ?: null);

        // Exhibitions — free-text blob split into one paragraph per row
        // so the JSON array satisfies the model cast without over-parsing.
        $ex = trim((string) ($item['exhibitions'] ?? ''));
        if ($ex !== '') {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $ex))));
            if ($lines) $set('previous_exhibitions', $lines);
        }

        $set('website', trim((string) ($item['website'] ?? '')) ?: null);

        // Social links — collect only the ones present.
        $social = [];
        foreach (['facebook_url' => 'facebook', 'twitter_url' => 'twitter', 'instagram_url' => 'instagram'] as $src => $key) {
            $url = trim((string) ($item[$src] ?? ''));
            if ($url !== '') $social[$key] = $url;
        }
        if ($social) $set('social_links', $social);

        // Country — artgalleria stores the country NAME ("Slovakia"),
        // art-db keys by Country.id. Try to match on name.
        $country = trim((string) ($item['country_code'] ?? ''));
        if ($country !== '') {
            $c = Country::whereRaw('LOWER(name) = ?', [strtolower($country)])->first();
            if ($c) $set('country_id', $c->id);
        }

        // Birth place — no dedicated column at artgalleria; take city if
        // present, prefixed with region when available.
        $city   = trim((string) ($item['city']   ?? ''));
        $region = trim((string) ($item['region'] ?? ''));
        $place  = trim(($city ?: '').($region ? ', '.$region : ''), ', ');
        if ($place !== '') $set('birth_place', $place);

        return $attrs;
    }
}
