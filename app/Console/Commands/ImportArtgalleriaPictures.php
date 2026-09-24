<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Download artgalleria artist profile pictures into art-db Artist.profile_image.
 * Input JSON: [{editId: number, pictures: [cdnUrl, ...]}] where the editId is
 * the artgalleria artist_profile_id and pictures is 0..N CDN URLs.
 *
 * The scrape can only see whichever variant the profile page embeds
 * (usually the '-t' thumbnail); before saving we rewrite it to the '-l'
 * large variant which artgalleria's CDN also serves.
 */
class ImportArtgalleriaPictures extends Command
{
    protected $signature = 'import:artgalleria-pictures
        {pictures : Pictures JSON from browser scraper}
        {artists : Artists JSON (same one import:artgalleria-artists consumed)}
        {--user= : Owner user id}
        {--dry-run}';

    protected $description = 'Download artgalleria artist pictures + set Artist.profile_image';

    public function handle(): int
    {
        $picPath  = $this->argument('pictures');
        $artPath  = $this->argument('artists');
        foreach ([$picPath, $artPath] as $p) {
            if (! is_file($p)) { $this->error("File not found: $p"); return 1; }
        }
        $userId = (int) $this->option('user');
        $user = User::find($userId);
        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error("User $userId not found or not Gallery"); return 1;
        }
        $dryRun = (bool) $this->option('dry-run');

        // Build editId → (first,last) lookup from the artists JSON.
        $nameById = [];
        foreach (json_decode(file_get_contents($artPath), true) as $a) {
            $nameById[(int) $a['editId']] = [$a['firstname'] ?? '', $a['lastname'] ?? ''];
        }

        $updated = $missing = $skipped = 0;
        foreach (json_decode(file_get_contents($picPath), true) as $i => $pic) {
            $editId = (int) $pic['editId'];
            $urls   = $pic['pictures'] ?? [];
            if (empty($urls)) { $skipped++; continue; }
            [$first, $last] = $nameById[$editId] ?? ['', ''];

            $needle = strtolower(preg_replace('/\s+/', ' ',
                str_replace('_', '', trim(($first ?? '').' '.($last ?? '')))));
            $needle = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $needle) ?: $needle;

            $artist = Artist::where('owner_user_id', $user->id)->get()
                ->first(function (Artist $a) use ($needle) {
                    $s = strtolower(preg_replace('/\s+/', ' ', str_replace('_', '', trim(($a->first_name ?? '').' '.($a->last_name ?? '')))));
                    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
                    return $s === $needle;
                });

            if (! $artist) {
                $missing++;
                $this->line(sprintf('  %3d. NO-ARTIST %s %s', $i+1, $first, $last));
                continue;
            }

            // Pick the largest available variant (-l > -o > -m > -s > -t).
            usort($urls, function ($a, $b) {
                $rank = ['-l.' => 5, '-o.' => 4, '-m.' => 3, '-s.' => 2, '-t.' => 1];
                $rk = fn($u) => collect($rank)->first(fn($v, $k) => str_contains($u, $k)) ?? 0;
                return $rk($b) - $rk($a);
            });
            $url = str_starts_with($urls[0], 'http') ? $urls[0] : 'https://'.$urls[0];
            // Upgrade -t to -l on the way out if the profile page only listed the thumbnail.
            $url = preg_replace('/-t\.(jpe?g|png|webp)$/i', '-l.$1', $url);

            if ($dryRun) {
                $this->line(sprintf('  %3d. WOULD SET  %s %s ← %s', $i+1, $first, $last, $url));
                $updated++; continue;
            }

            try {
                $r = Http::timeout(60)->get($url);
                if (! $r->successful()) {
                    // Fallback to whatever URL was actually seen if -l 404s.
                    $r = Http::timeout(60)->get(str_starts_with($urls[0], 'http') ? $urls[0] : 'https://'.$urls[0]);
                }
                if (! $r->successful()) {
                    $this->warn(sprintf('  %3d. HTTP %d fetching %s', $i+1, $r->status(), $url));
                    $missing++; continue;
                }
                $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'artists/'.Str::ulid().'.'.strtolower($ext);
                Storage::disk('public')->put($filename, $r->body());
                $artist->update(['profile_image' => $filename]);
                $updated++;
            } catch (\Throwable $e) {
                $this->warn(sprintf('  %3d. ERROR %s: %s', $i+1, $url, $e->getMessage()));
                $missing++;
            }
        }

        $this->info(sprintf('Done: %d updated, %d missing/failed, %d skipped (no picture)', $updated, $missing, $skipped));
        return 0;
    }
}
