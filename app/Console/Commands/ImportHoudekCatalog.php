<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Gallery;
use App\Models\Medium;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-off importer for Vladimír Houdek's "Medzi konečnosťou a nekonečnosťou"
 * catalogue (COMMA Gallery × Roman Fecik Gallery, 17/09–29/10/2026).
 *
 * 20 artworks in the catalogue, first one already added manually — this
 * command imports the remaining 19 with photos extracted from the source
 * PDF into `database/imports/houdek-2026/houdek-pNN.jpg`.
 *
 * Idempotent on (owner_user_id, inventory_id) = `VH-2026-NN` so a re-run
 * is safe.
 *
 * Usage:
 *   php artisan import:houdek-catalog --user=<gallery_user_id> [--dry-run]
 */
class ImportHoudekCatalog extends Command
{
    protected $signature = 'import:houdek-catalog
        {--user= : Owner Gallery user id (defaults to info@rfg.sk)}
        {--dry-run : Print plan without writing DB or copying images}';

    protected $description = 'Import Vladimír Houdek catalogue (19 remaining artworks) into art-db';

    /**
     * Per-page metadata. Page 4 (first artwork) is intentionally omitted —
     * already in the database. Prices, sizes and mediums come straight
     * from the catalogue caption on each page.
     */
    protected const ITEMS = [
        // Series 1 — Paralelné Ja, boards
        ['page' => 5,  'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        ['page' => 6,  'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        ['page' => 8,  'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        ['page' => 9,  'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        ['page' => 10, 'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        ['page' => 11, 'year' => 2026, 'h' => 60,   'w' => 40,   'price' => 5020, 'medium' => 'Akryl a koláž na doske'],
        // Series 2 — tall canvases
        ['page' => 13, 'year' => 2026, 'h' => 80,   'w' => 40,   'price' => 6020, 'medium' => 'Akryl, tuš a koláž na plátne'],
        ['page' => 14, 'year' => 2026, 'h' => 80,   'w' => 40,   'price' => 6020, 'medium' => 'Akryl, tuš a koláž na plátne'],
        ['page' => 15, 'year' => 2026, 'h' => 80,   'w' => 40,   'price' => 6020, 'medium' => 'Akryl, tuš a koláž na plátne'],
        // Series 3 — small, older
        ['page' => 18, 'year' => 2019, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        ['page' => 19, 'year' => 2019, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        ['page' => 20, 'year' => 2019, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        ['page' => 21, 'year' => 2018, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        ['page' => 22, 'year' => 2019, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        ['page' => 23, 'year' => 2019, 'h' => 30,   'w' => 24,   'price' => 2730, 'medium' => 'Akryl, voskový pastel a koláž na plátne'],
        // Series 4 — framed paper collages
        ['page' => 26, 'year' => 2026, 'h' => 61.5, 'w' => 43.5, 'price' => 3630, 'medium' => 'Koláž na papieri, rámované'],
        ['page' => 27, 'year' => 2026, 'h' => 61.5, 'w' => 43.5, 'price' => 3630, 'medium' => 'Koláž na papieri, rámované'],
        ['page' => 28, 'year' => 2026, 'h' => 61.5, 'w' => 43.5, 'price' => 3630, 'medium' => 'Koláž na papieri, rámované'],
        ['page' => 29, 'year' => 2026, 'h' => 61.5, 'w' => 43.5, 'price' => 3630, 'medium' => 'Koláž na papieri, rámované'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $gallery = Gallery::where('owner_user_id', $user->id)->first();
        if (! $gallery) {
            $this->error("Gallery record for user #{$user->id} not found.");

            return self::FAILURE;
        }

        $artist = $this->findOrCreateArtist($user, $gallery, $dryRun);

        $importDir = base_path('database/imports/houdek-2026');
        if (! is_dir($importDir)) {
            $this->error("Import directory not found: {$importDir}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s %d Houdek artworks into "%s" (user #%d) — artist: %s',
            $dryRun ? '[DRY RUN] Would import' : 'Importing',
            count(self::ITEMS),
            $gallery->name,
            $user->id,
            $artist?->display_name ?? '—',
        ));

        $created = $updated = $skipped = $failed = 0;
        foreach (self::ITEMS as $i => $item) {
            $inventoryId = sprintf('VH-2026-%02d', $item['page']);
            $label = sprintf('p%02d %s %s×%s cm', $item['page'], $item['year'], $item['h'], $item['w']);

            try {
                $result = $this->importOne($item, $inventoryId, $user, $artist, $importDir, $dryRun);
                $this->line(sprintf('  %2d. %-8s %s  [%s]', $i + 1, strtoupper($result), $label, $inventoryId));
                $$result++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('  %2d. FAILED  %s — %s', $i + 1, $label, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf('Done: %d created, %d updated, %d skipped, %d failed', $created, $updated, $skipped, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        $userId = $this->option('user');
        if ($userId) {
            $user = User::find((int) $userId);
        } else {
            $user = User::where('email', 'info@rfg.sk')->first();
        }

        if (! $user) {
            $this->error('Owner user not found. Pass --user=<id> or ensure info@rfg.sk exists.');

            return null;
        }

        if ($user->role !== UserRole::Gallery) {
            $this->error("User #{$user->id} is not a Gallery role.");

            return null;
        }

        return $user;
    }

    protected function findOrCreateArtist(User $user, Gallery $gallery, bool $dryRun): ?Artist
    {
        // Diacritics-tolerant lookup — the manually-added first artwork's
        // artist may be stored as "Vladimir Houdek" or "Vladimír Houdek".
        $artist = Artist::where('owner_user_id', $user->id)
            ->where('last_name', 'Houdek')
            ->where(function ($q) {
                $q->where('first_name', 'Vladimír')
                  ->orWhere('first_name', 'Vladimir');
            })
            ->first();

        if ($artist) {
            $this->attachArtistToGallery($artist, $gallery, $dryRun);

            return $artist;
        }

        if ($dryRun) {
            $this->line('     · would create Artist: Vladimír Houdek');

            return null;
        }

        $artist = Artist::create([
            'first_name'    => 'Vladimír',
            'last_name'     => 'Houdek',
            'owner_user_id' => $user->id,
            'is_published'  => false,
        ]);

        $this->attachArtistToGallery($artist, $gallery, $dryRun);

        return $artist;
    }

    protected function attachArtistToGallery(Artist $artist, Gallery $gallery, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }
        if (! $gallery->artists()->where('artists.id', $artist->id)->exists()) {
            $gallery->artists()->attach($artist->id);
        }
    }

    protected function importOne(array $item, string $inventoryId, User $user, ?Artist $artist, string $importDir, bool $dryRun): string
    {
        $existing = Artwork::where('owner_user_id', $user->id)
            ->where('inventory_id', $inventoryId)
            ->first();

        $mediumId = $this->resolveMediumId($item['medium'], $user, $dryRun);

        $attributes = [
            'title'            => 'Bez názvu',
            'artist_id'        => $artist?->id,
            'year_created'     => $item['year'],
            'medium_id'        => $mediumId,
            'materials'        => $item['medium'],
            'height_cm'        => $item['h'],
            'width_cm'         => $item['w'],
            'price'            => $item['price'],
            'currency'         => 'EUR',
            'price_on_request' => false,
            'is_published'     => false,
            'owner_user_id'    => $user->id,
            'inventory_id'     => $inventoryId,
        ];

        if ($dryRun) {
            return $existing ? 'updated' : 'created';
        }

        $primaryImage = $this->copyPrimaryImage($item['page'], $importDir, $user->id, $inventoryId);
        if ($primaryImage) {
            $attributes['primary_image'] = $primaryImage;
        }

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Artwork::create($attributes);

        return 'created';
    }

    protected function resolveMediumId(string $mediumName, User $user, bool $dryRun): ?int
    {
        $medium = Medium::where('owner_user_id', $user->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($mediumName)])
            ->first();

        if ($medium) {
            return $medium->id;
        }

        if ($dryRun) {
            return null;
        }

        $medium = Medium::create([
            'name'          => $mediumName,
            'owner_user_id' => $user->id,
        ]);

        return $medium->id;
    }

    protected function copyPrimaryImage(int $page, string $importDir, int $ownerId, string $inventoryId): ?string
    {
        $src = sprintf('%s/houdek-p%02d.jpg', $importDir, $page);
        if (! is_file($src)) {
            $this->warn("     · image missing: houdek-p{$page}.jpg");
            return null;
        }

        $dest = sprintf('artworks/%d/%s.jpg', $ownerId, Str::slug($inventoryId));
        Storage::disk('public')->put($dest, file_get_contents($src));

        return $dest;
    }
}
