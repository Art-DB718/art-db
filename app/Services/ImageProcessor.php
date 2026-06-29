<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

/**
 * Compresses uploaded images in place and generates _thumb / _medium variants
 * alongside the original. Variant naming convention:
 *
 *   artworks/abc.jpg          ← original (optimized)
 *   artworks/abc_medium.jpg   ← 1200px on the longest side
 *   artworks/abc_thumb.jpg    ← 400px on the longest side
 *
 * Use `ImageProcessor::pathFor($path, 'thumb')` to get a variant path.
 */
class ImageProcessor
{
    /** @var array<string, int> Longest-side targets per variant. */
    protected const VARIANTS = [
        'medium' => 1200,
        'thumb'  => 400,
    ];

    /** Formats we actually process. SVG passes through untouched. */
    protected const HANDLED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public static function process(string $path, string $disk = 'public'): void
    {
        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, self::HANDLED_EXTENSIONS, true)) {
            return;
        }

        $absolute = Storage::disk($disk)->path($path);

        // 1. Optimize the original in place (jpegoptim / optipng / pngquant / cwebp).
        try {
            ImageOptimizer::optimize($absolute);
        } catch (\Throwable $e) {
            logger()->warning('ImageOptimizer failed for '.$path.': '.$e->getMessage());
        }

        // 2. Generate downscaled variants beside the original.
        foreach (self::VARIANTS as $name => $maxSide) {
            $variantPath = self::pathFor($path, $name);

            // Skip if already generated and newer than the original.
            if (Storage::disk($disk)->exists($variantPath)
                && Storage::disk($disk)->lastModified($variantPath) >= Storage::disk($disk)->lastModified($path)) {
                continue;
            }

            try {
                $img = Image::decodePath($absolute);
                $img->scaleDown(width: $maxSide, height: $maxSide);

                $tmp = sys_get_temp_dir().'/'.\Illuminate\Support\Str::random(12).'.'.$ext;
                $img->save($tmp);
                ImageOptimizer::optimize($tmp);
                Storage::disk($disk)->put($variantPath, file_get_contents($tmp));
                @unlink($tmp);
            } catch (\Throwable $e) {
                logger()->warning("ImageProcessor variant '$name' failed for $path: ".$e->getMessage());
            }
        }
    }

    /** Process every path in a JSON array column (gallery_images, etc.). */
    public static function processMany(?array $paths, string $disk = 'public'): void
    {
        foreach ((array) $paths as $path) {
            if (is_string($path) && $path !== '') {
                self::process($path, $disk);
            }
        }
    }

    /** Returns the path for a variant (or original if $variant === 'original'). */
    public static function pathFor(string $path, string $variant = 'original'): string
    {
        if ($variant === 'original' || ! isset(self::VARIANTS[$variant])) {
            return $path;
        }

        $dir   = ltrim(dirname($path), '.');
        $name  = pathinfo($path, PATHINFO_FILENAME);
        $ext   = pathinfo($path, PATHINFO_EXTENSION);
        $base  = $dir ? $dir.'/' : '';

        return $base.$name.'_'.$variant.'.'.$ext;
    }
}
