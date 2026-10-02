<?php

use Illuminate\Support\Facades\URL;

if (! function_exists('signed_image_url')) {
    /**
     * Signed, time-limited URL for a public-disk file. Used by every
     * public Blade so a scraper can't bulk-download by guessing paths.
     *
     * Returns null if $path is empty/null so callers can keep their
     * existing `@if ($image) <img src=...> @endif` guards.
     */
    function signed_image_url(?string $path, int $minutes = 15): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'image.serve',
            now()->addMinutes($minutes),
            ['path' => $path],
        );
    }
}
