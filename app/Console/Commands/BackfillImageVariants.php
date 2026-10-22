<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Collection as ArtworkCollection;
use App\Models\Exhibition;
use App\Models\Gallery;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;

/**
 * One-off (idempotent) backfill: walks every image column we have on the
 * public web and ensures the `_thumb` and `_medium` variants exist on
 * disk. Safe to re-run — ImageProcessor skips variants that are already
 * newer than the original.
 *
 * On a ~1000-artwork tenant this trims a listing page from ~12 MB of
 * JPEG downloads to under 1 MB.
 */
class BackfillImageVariants extends Command
{
    protected $signature = 'images:backfill-variants
                            {--only= : Comma-separated list of models to process (artworks,artists,exhibitions,collections,galleries)}
                            {--chunk=50 : Rows per chunk}';

    protected $description = 'Generate missing _thumb / _medium variants for every public image on disk.';

    public function handle(): int
    {
        $only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : ['artworks', 'artists', 'exhibitions', 'collections', 'galleries'];

        $chunk = max(1, (int) $this->option('chunk'));

        foreach ($only as $model) {
            match ($model) {
                'artworks'    => $this->doArtworks($chunk),
                'artists'     => $this->doArtists($chunk),
                'exhibitions' => $this->doExhibitions($chunk),
                'collections' => $this->doCollections($chunk),
                'galleries'   => $this->doGalleries($chunk),
                default       => $this->warn("Unknown model: $model"),
            };
        }

        return self::SUCCESS;
    }

    protected function doArtworks(int $chunk): void
    {
        $total = Artwork::withTrashed()->count();
        $this->info("Artworks: $total rows");
        $bar = $this->output->createProgressBar($total);
        Artwork::withTrashed()
            ->select(['id', 'primary_image', 'gallery_images'])
            ->chunkById($chunk, function ($rows) use ($bar) {
                foreach ($rows as $a) {
                    ImageProcessor::process((string) $a->primary_image);
                    ImageProcessor::processMany($a->gallery_images ?? []);
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
    }

    protected function doArtists(int $chunk): void
    {
        $total = Artist::withTrashed()->count();
        $this->info("Artists: $total rows");
        $bar = $this->output->createProgressBar($total);
        Artist::withTrashed()
            ->select(['id', 'profile_image', 'cover_image'])
            ->chunkById($chunk, function ($rows) use ($bar) {
                foreach ($rows as $a) {
                    ImageProcessor::process((string) $a->profile_image);
                    ImageProcessor::process((string) $a->cover_image);
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
    }

    protected function doExhibitions(int $chunk): void
    {
        $total = Exhibition::withTrashed()->count();
        $this->info("Exhibitions: $total rows");
        $bar = $this->output->createProgressBar($total);
        Exhibition::withTrashed()
            ->select(['id', 'poster_image', 'gallery_images'])
            ->chunkById($chunk, function ($rows) use ($bar) {
                foreach ($rows as $e) {
                    ImageProcessor::process((string) $e->poster_image);
                    ImageProcessor::processMany($e->gallery_images ?? []);
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
    }

    protected function doCollections(int $chunk): void
    {
        $total = ArtworkCollection::withTrashed()->count();
        $this->info("Collections: $total rows");
        $bar = $this->output->createProgressBar($total);
        ArtworkCollection::withTrashed()
            ->select(['id', 'cover_image'])
            ->chunkById($chunk, function ($rows) use ($bar) {
                foreach ($rows as $c) {
                    ImageProcessor::process((string) $c->cover_image);
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
    }

    protected function doGalleries(int $chunk): void
    {
        $total = Gallery::withTrashed()->count();
        $this->info("Galleries: $total rows");
        $bar = $this->output->createProgressBar($total);
        Gallery::withTrashed()
            ->select(['id', 'logo', 'cover_image'])
            ->chunkById($chunk, function ($rows) use ($bar) {
                foreach ($rows as $g) {
                    ImageProcessor::process((string) ($g->logo ?? ''));
                    ImageProcessor::process((string) ($g->cover_image ?? ''));
                    $bar->advance();
                }
            });
        $bar->finish();
        $this->newLine();
    }
}
