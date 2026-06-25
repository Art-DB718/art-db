<?php

namespace App\Filament\Concerns;

/**
 * Add to a Filament Resource to hide it from non-gallery / non-admin users
 * (e.g. Artist accounts). Hides from navigation AND blocks direct URL access.
 */
trait GalleryOnly
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isGallery());
    }
}
