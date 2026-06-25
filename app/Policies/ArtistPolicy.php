<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function view(User $user, Artist $artist): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $artist->owner_user_id === $user->id;
        if ($user->isCollector()) return $artist->owner_user_id === $user->id || (bool) $artist->is_published;
        return false;
    }

    public function create(User $user): bool
    {
        if ($user->isGallery())   return true;
        // Artist user can create their own profile if they don't have one yet.
        if ($user->isArtist())    return ! $user->artistProfile()->exists();
        if ($user->isCollector()) return true; // private records in collector's archive
        return false;
    }

    public function update(User $user, Artist $artist): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $artist->owner_user_id === $user->id;
        if ($user->isCollector()) return $artist->owner_user_id === $user->id;
        return false;
    }

    public function delete(User $user, Artist $artist): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isCollector()) return $artist->owner_user_id === $user->id;
        return false;
    }

    public function restore(User $user, Artist $artist): bool
    {
        return $user->isGallery();
    }

    public function forceDelete(User $user, Artist $artist): bool
    {
        return $user->isGallery();
    }
}
