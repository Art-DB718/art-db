<?php

namespace App\Policies;

use App\Models\PrivateRoom;
use App\Models\User;

class PrivateRoomPolicy
{
    // Private rooms = sales/CRM nástroj galérie.

    public function viewAny(User $user): bool
    {
        return $user->isGallery();
    }

    public function view(User $user, PrivateRoom $privateRoom): bool
    {
        return $user->isGallery();
    }

    public function create(User $user): bool
    {
        return $user->isGallery();
    }

    public function update(User $user, PrivateRoom $privateRoom): bool
    {
        return $user->isGallery();
    }

    public function delete(User $user, PrivateRoom $privateRoom): bool
    {
        return $user->isGallery();
    }

    public function restore(User $user, PrivateRoom $privateRoom): bool
    {
        return $user->isGallery();
    }

    public function forceDelete(User $user, PrivateRoom $privateRoom): bool
    {
        return $user->isGallery();
    }
}
