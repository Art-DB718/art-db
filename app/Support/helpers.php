<?php

use App\Support\ArtTermTranslator;
use Illuminate\Support\Facades\URL;

if (! function_exists('signed_image_url')) {
    /**
     * Signed, time-limited URL for a public-disk file. Used by every
     * public Blade so a scraper can't bulk-download by guessing paths.
     *
     * $variant picks a downscaled copy from ImageProcessor
     * ('thumb' ≈ 400w, 'medium' ≈ 1200w, 'original' for full size).
     * If the variant doesn't exist on disk yet (older artworks that
     * haven't been reprocessed), falls through to the original path so
     * nothing 404s.
     *
     * Returns null if $path is empty/null so callers can keep their
     * existing `@if ($image) <img src=...> @endif` guards.
     */
    function signed_image_url(?string $path, string $variant = 'original', int $minutes = 15): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $resolved = $path;
        if ($variant !== 'original') {
            $variantPath = \App\Services\ImageProcessor::pathFor($path, $variant);
            if ($variantPath !== $path
                && \Illuminate\Support\Facades\Storage::disk('public')->exists($variantPath)) {
                $resolved = $variantPath;
            }
        }

        return URL::temporarySignedRoute(
            'image.serve',
            now()->addMinutes($minutes),
            ['path' => $resolved],
        );
    }
}

if (! function_exists('art_term_en')) {
    /**
     * English display label for an art-domain term (medium / genre /
     * free-text material). Owner-provided $name_en wins; otherwise we
     * fall back to a dictionary pass over $name. Returns $name as a
     * last resort so a missing translation never blanks the page.
     */
    function art_term_en(?string $name, ?string $name_en = null): ?string
    {
        if (filled($name_en)) {
            return $name_en;
        }
        if (! filled($name)) {
            return $name;
        }
        return ArtTermTranslator::translate($name);
    }
}
