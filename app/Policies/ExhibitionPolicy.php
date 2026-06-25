<?php

namespace App\Policies;

use App\Models\Exhibition;
use App\Models\User;

class ExhibitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function view(User $user, Exhibition $exhibition): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist()) {
            // Artist môže pozerať výstavu, ktorá obsahuje jeho dielo.
            return $exhibition->artworks()->where('artist_id', $user->artistProfile?->id)->exists();
        }
        if ($user->isCollector()) return (bool) $exhibition->is_published || $exhibition->owner_user_id === $user->id;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function update(User $user, Exhibition $exhibition): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isCollector()) return $exhibition->owner_user_id === $user->id;
        return false;
    }

    public function delete(User $user, Exhibition $exhibition): bool
    {
        return $this->update($user, $exhibition);
    }

    public function restore(User $user, Exhibition $exhibition): bool
    {
        return $this->update($user, $exhibition);
    }

    public function forceDelete(User $user, Exhibition $exhibition): bool
    {
        return $user->isGallery();
    }
}
