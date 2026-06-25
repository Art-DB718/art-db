<?php

namespace App\Policies;

use App\Models\Artwork;
use App\Models\User;

class ArtworkPolicy
{
    public function viewAny(User $user): bool
    {
        // Admin (skipped via Gate::before), Gallery, Artist a Collector môžu pozerať katalóg.
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function view(User $user, Artwork $artwork): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $artwork->owner_user_id === $user->id;
        if ($user->isCollector()) return $artwork->owner_user_id === $user->id || (bool) $artwork->is_published;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function update(User $user, Artwork $artwork): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $artwork->owner_user_id === $user->id;
        if ($user->isCollector()) return $artwork->owner_user_id === $user->id;
        return false;
    }

    public function delete(User $user, Artwork $artwork): bool
    {
        return $this->update($user, $artwork);
    }

    public function restore(User $user, Artwork $artwork): bool
    {
        return $this->update($user, $artwork);
    }

    public function forceDelete(User $user, Artwork $artwork): bool
    {
        return $user->isGallery();
    }
}
