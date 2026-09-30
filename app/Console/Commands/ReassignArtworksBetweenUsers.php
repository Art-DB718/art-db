<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Move artworks by named artists from one owner user to another.
 * Diacritics-insensitive last-name match, dry-run by default.
 *
 * Example:
 *   php artisan artworks:reassign \
 *     --from=old@example.com \
 *     --to=new@example.com \
 *     --artists="Želibská,Šumichrast" --dry-run
 *
 *   php artisan artworks:reassign \
 *     --from=old@example.com --to=new@example.com \
 *     --artists="Želibská,Šumichrast" --force
 */
class ReassignArtworksBetweenUsers extends Command
{
    protected $signature = 'artworks:reassign
        {--from= : Source user e-mail}
        {--to= : Target user e-mail}
        {--artists= : Comma-separated artist LAST names to include}
        {--dry-run : Print what would move without writing to DB}
        {--force : Actually perform the move (without this it is dry-run)}';

    protected $description = 'Move artworks by named artists from one owner user to another';

    public function handle(): int
    {
        $fromEmail = strtolower(trim((string) $this->option('from')));
        $toEmail   = strtolower(trim((string) $this->option('to')));
        $names     = collect(explode(',', (string) $this->option('artists')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->values();

        if ($fromEmail === '' || $toEmail === '' || $names->isEmpty()) {
            $this->error('--from, --to and --artists are required.');
            return self::FAILURE;
        }

        $from = User::whereRaw('LOWER(email) = ?', [$fromEmail])->first();
        $to   = User::whereRaw('LOWER(email) = ?', [$toEmail])->first();

        if (! $from || ! $to) {
            $this->error("Source or target user not found. from={$fromEmail}, to={$toEmail}");
            return self::FAILURE;
        }
        if ($from->id === $to->id) {
            $this->error('Source and target are the same user — nothing to do.');
            return self::FAILURE;
        }

        $dryRun = ! $this->option('force');

        // Diacritics-tolerant match on artist last_name. Postgres unaccent()
        // extension is already enabled on prod. Note: PHP strtolower() is
        // ASCII-only, so we let SQL lower() handle both column and param
        // (Postgres lower() is locale-aware, MySQL LOWER() too).
        $driver = \DB::connection()->getDriverName();
        $lastNames = $names->all();

        $artistsQuery = Artist::query()->where('owner_user_id', $from->id);
        if ($driver === 'pgsql') {
            $artistsQuery->where(function ($q) use ($lastNames) {
                foreach ($lastNames as $ln) {
                    $q->orWhereRaw('unaccent(lower(last_name)) = unaccent(lower(?))', [$ln]);
                }
            });
        } else {
            $artistsQuery->where(function ($q) use ($lastNames) {
                foreach ($lastNames as $ln) {
                    $q->orWhereRaw('LOWER(last_name) = LOWER(?)', [$ln]);
                }
            });
        }

        $artists = $artistsQuery->get(['id', 'first_name', 'last_name']);

        if ($artists->isEmpty()) {
            $this->warn('No artists matched under the source user.');
            return self::SUCCESS;
        }

        $this->info("Matched artists under {$from->email}:");
        foreach ($artists as $a) {
            $this->line("  · #{$a->id}  {$a->first_name} {$a->last_name}");
        }

        $artworks = Artwork::query()
            ->where('owner_user_id', $from->id)
            ->whereIn('artist_id', $artists->pluck('id'))
            ->get(['id', 'inventory_id', 'title', 'artist_id']);

        $this->newLine();
        $this->info(sprintf(
            '%d artwork(s) would move from %s (#%d) to %s (#%d)',
            $artworks->count(),
            $from->email, $from->id,
            $to->email,   $to->id,
        ));

        if ($this->getOutput()->isVerbose()) {
            foreach ($artworks as $aw) {
                $this->line('    - '.($aw->inventory_id ?: '—').'  '.$aw->title);
            }
        }

        if ($dryRun || $this->option('dry-run')) {
            $this->warn('DRY RUN — nothing was written. Re-run with --force to apply.');
            return self::SUCCESS;
        }

        \DB::transaction(function () use ($artworks, $artists, $to) {
            Artwork::whereIn('id', $artworks->pluck('id'))->update(['owner_user_id' => $to->id]);
            Artist::whereIn('id', $artists->pluck('id'))->update(['owner_user_id' => $to->id]);
        });

        $this->info('Done — artworks + their artist rows reassigned.');
        return self::SUCCESS;
    }
}
