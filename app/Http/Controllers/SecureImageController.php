<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Serves files from the public disk through a Laravel signed URL with a
 * short TTL. Keeps bulk scrapers from downloading the entire storage
 * tree by guessing paths — each image URL is tied to a specific path,
 * signed with the app key, and expires after N minutes.
 *
 * Admins still see images in the Filament admin via direct Storage::url
 * (behind auth); only the public pages route through this controller.
 */
class SecureImageController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            throw new HttpException(403, 'Expired or invalid image link');
        }

        $path = (string) $request->query('path', '');

        // Belt-and-braces path safety: strictly reject any traversal
        // attempt. The signature already covers this (an attacker can't
        // forge it) but we double-check in case the signing secret ever
        // leaks.
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new HttpException(400, 'Bad image path');
        }

        if (! Storage::disk('public')->exists($path)) {
            throw new HttpException(404, 'Image not found');
        }

        // Stream the file with aggressive browser caching — the URL
        // itself expires after 15 min, so revalidating isn't needed
        // within that window.
        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
