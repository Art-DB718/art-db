<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import artworks scraped from artgalleria.com into art-db.
 *
 * Usage:
 *   php artisan import:artgalleria path/to/scraped.json --user=8 [--dry-run]
 *
 * The JSON is produced by a browser-side scraper (Claude in Chrome) that
 * hits the artgalleria admin endpoints; this command consumes that JSON
 * offline, downloads images from the public CDN, and writes Artist +
 * Artwork rows owned by the target Gallery user. Everything imported is
 * left is_published=false so the gallery can review before publishing.
 */
class ImportArtgalleria extends Command
{
    protected $signature = 'import:artgalleria
        {json : Path to the scraped JSON file}
        {--user= : Owner user id (Gallery role)}
        {--dry-run : Print plan without writing DB or downloading images}
        {--limit= : Only import the first N artworks}';

    protected $description = 'Import artworks from artgalleria.com scrape JSON into art-db';

    public function handle(): int
    {
        $path = $this->argument('json');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $userId = (int) $this->option('user');
        $user   = User::find($userId);
        if (! $user || $user->role !== UserRole::Gallery) {
            $this->error("User id {$userId} not found or not a Gallery role.");

            return self::FAILURE;
        }

        $gallery = Gallery::where('owner_user_id', $user->id)->first();
        if (! $gallery) {
            $this->error("Gallery record for user {$userId} not found.");

            return self::FAILURE;
        }

        $items = json_decode(file_get_contents($path), true);
        if (! is_array($items)) {
            $this->error('JSON is not a top-level array.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit') ?: null;
        if ($limit) {
            $items = array_slice($items, 0, $limit);
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s %d artworks into "%s" (user #%d) …',
            $dryRun ? '[DRY RUN] Would import' : 'Importing',
            count($items),
            $gallery->name,
            $user->id,
        ));

        $created = $updated = $skipped = $failed = 0;
        foreach ($items as $i => $item) {
            $label = ($item['inventory_id'] ?? '?').' — '.($item['title'] ?? '?');
            try {
                $result = $this->importOne($item, $user, $gallery, $dryRun);
                $this->line(sprintf('  %2d. %-8s %s', $i + 1, strtoupper($result), $label));
                $$result++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('  %2d. FAILED  %s — %s', $i + 1, $label, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf('Done: %d created, %d updated, %d skipped, %d failed', $created, $updated, $skipped, $failed));

        return self::SUCCESS;
    }

    /**
     * Import one artwork; returns 'created', 'updated', or 'skipped'.
     */
    protected function importOne(array $item, User $user, Gallery $gallery, bool $dryRun): string
    {
        $inventoryId = $item['inventory_id'] ?? null;
        if (! $inventoryId) {
            throw new \RuntimeException('Missing inventory_id');
        }

        $artist = $this->findOrCreateArtist($item['artist_name'] ?? null, $user, $gallery, $dryRun);

        $existing = Artwork::where('owner_user_id', $user->id)
            ->where('inventory_id', $inventoryId)
            ->first();

        $attributes = $this->mapArtworkAttributes($item, $user, $artist);

        if ($dryRun) {
            return $existing ? 'updated' : 'created';
        }

        // Download images (primary + gallery) before creating record.
        [$primaryImage, $galleryImages] = $this->downloadImages($item['images'] ?? []);
        $attributes['primary_image'] = $primaryImage;
        $attributes['gallery_images'] = $galleryImages;

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Artwork::create($attributes);

        return 'created';
    }

    protected function findOrCreateArtist(?string $name, User $user, Gallery $gallery, bool $dryRun): ?Artist
    {
        if (! $name || in_array(trim($name), ['', '_ _', '-'], true)) {
            return null;
        }

        // Split "Rudolf Fila" → first=Rudolf last=Fila. Handles single-name too.
        $parts = preg_split('/\s+/', trim($name));
        $first = count($parts) > 1 ? array_shift($parts) : '';
        $last  = implode(' ', $parts);

        $artist = Artist::where('owner_user_id', $user->id)
            ->where('first_name', $first)
            ->where('last_name', $last)
            ->first();

        if ($artist) {
            $this->attachArtistToGallery($artist, $gallery, $dryRun);

            return $artist;
        }

        if ($dryRun) {
            $this->line("     · would create Artist: {$first} {$last}");

            return null;
        }

        $artist = Artist::create([
            'first_name'    => $first,
            'last_name'     => $last,
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
        $gallery->artists()->syncWithoutDetaching([$artist->id]);
    }

    protected function mapArtworkAttributes(array $item, User $user, ?Artist $artist): array
    {
        $unit = strtolower($item['measurement'] ?? 'cm');
        $toCm = fn($v) => $unit === 'in' && $v ? round((float) $v * 2.54, 2) : (float) $v;

        return array_filter([
            'inventory_id'          => $item['inventory_id'],
            'artist_id'             => $artist?->id,
            'owner_user_id'         => $user->id,
            'title'                 => $item['title'] ?? 'Untitled',
            'status_id'             => $this->mapStatusId($item['artwork_status_id'] ?? null),
            'year_created'          => $this->parseYear($item['artwork_year'] ?? null),
            'materials'             => $item['medium_text'] ?? null,
            'height_cm'             => isset($item['height']) ? $toCm($item['height']) : null,
            'width_cm'              => isset($item['width']) ? $toCm($item['width']) : null,
            'depth_cm'              => isset($item['depth']) ? $toCm($item['depth']) : null,
            'frame_height_cm'       => isset($item['framed_height']) ? $toCm($item['framed_height']) : null,
            'frame_width_cm'        => isset($item['framed_width']) ? $toCm($item['framed_width']) : null,
            'frame_depth_cm'        => isset($item['framed_depth']) ? $toCm($item['framed_depth']) : null,
            'is_framed'             => ! empty($item['framed']),
            'is_signed'             => ! empty($item['signature']),
            'signature_description' => $item['signature'] ?? null,
            'description'           => $item['description'] ?? null,
            'provenance'            => $item['provenance'] ?? null,
            'literature'            => $item['literature'] ?? null,
            'exhibition_history'    => $item['exhibitions'] ?? null,
            'price'                 => isset($item['retail_price']) ? (float) $item['retail_price'] : null,
            'currency'              => $item['currency_code'] ?? 'EUR',
            'purchase_price'        => isset($item['purchase_price']) && (float) $item['purchase_price'] > 0
                ? (float) $item['purchase_price'] : null,
            'insurance_value'       => isset($item['insured_value']) && (float) $item['insured_value'] > 0
                ? (float) $item['insured_value'] : null,
            'is_published'          => false,
        ], fn($v) => $v !== null && $v !== '' && $v !== false);
    }

    /**
     * Map an artgalleria status label ("For Sale", "Sold", …) to the id
     * of the matching art-db ArtworkStatus row. Cached per run to avoid
     * hammering the reference table on every artwork.
     */
    protected array $statusCache = [];

    protected function mapStatusId(?string $label): ?int
    {
        if (! $label) {
            return null;
        }
        $label = trim($label);
        if ($label === '' || in_array(strtolower($label), ['0', 'null'], true)) {
            return null;
        }
        if (array_key_exists($label, $this->statusCache)) {
            return $this->statusCache[$label];
        }
        // Explicit artgalleria → art-db name aliases; anything not listed here
        // is matched case-insensitively against ArtworkStatus.name as-is.
        $aliases = [
            'For Sale'         => 'For sale',
            'For Rent'         => 'for rent',
            'For Sale or Rent' => 'For sale or rent',
            'Sold'             => 'Sold',
            'Not Available'    => 'Not for sale',
            'Details Pending'  => 'Details pending',
        ];
        $target = $aliases[$label] ?? $label;
        $row = \App\Models\ArtworkStatus::whereRaw('LOWER(name) = ?', [strtolower($target)])->first();

        return $this->statusCache[$label] = $row?->id;
    }

    protected function parseYear(?string $raw): ?int
    {
        if (! $raw) {
            return null;
        }
        if (preg_match('/\d{4}/', $raw, $m)) {
            return (int) $m[0];
        }

        return null;
    }

    /**
     * Download all images to storage/app/public/artworks and return
     * [primaryImage, galleryImages[]] as relative paths.
     */
    protected function downloadImages(array $images): array
    {
        $primary = null;
        $others = [];

        foreach ($images as $img) {
            $url  = $img['largeUrl'] ?? $img['thumbnailUrl'] ?? null;
            $name = $img['name']     ?? 'image.jpg';
            if (! $url) {
                continue;
            }
            $path = $this->downloadOne($url, $name);
            if (! $path) {
                continue;
            }
            if (! empty($img['main']) && ! $primary) {
                $primary = $path;
            } else {
                $others[] = $path;
            }
        }

        // If no image was flagged as main, use the first one.
        if (! $primary && $others) {
            $primary = array_shift($others);
        }

        return [$primary, $others ?: null];
    }

    protected function downloadOne(string $url, string $originalName): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
            if (! $response->successful()) {
                $this->warn("     · image fetch failed [{$response->status()}] {$url}");

                return null;
            }
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $ext = strtolower($ext);
            $filename = 'artworks/'.Str::ulid().'.'.$ext;
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (\Throwable $e) {
            $this->warn("     · image download error: {$e->getMessage()}");

            return null;
        }
    }
}
