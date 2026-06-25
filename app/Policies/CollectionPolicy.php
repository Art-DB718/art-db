<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function view(User $user, Collection $collection): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $collection->owner_user_id === $user->id;
        if ($user->isCollector()) return $collection->owner_user_id === $user->id;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isGallery() || $user->isArtist() || $user->isCollector();
    }

    public function update(User $user, Collection $collection): bool
    {
        if ($user->isGallery())   return true;
        if ($user->isArtist())    return $collection->owner_user_id === $user->id;
        if ($user->isCollector()) return $collection->owner_user_id === $user->id;
        return false;
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->update($user, $collection);
    }

    public function restore(User $user, Collection $collection): bool
    {
        return $this->update($user, $collection);
    }

    public function forceDelete(User $user, Collection $collection): bool
    {
        return $user->isGallery();
    }
}
