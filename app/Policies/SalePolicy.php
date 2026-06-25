<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    // Sales sú interná Gallery agenda — Artist a Collector nemajú prístup.

    public function viewAny(User $user): bool
    {
        return $user->isGallery();
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->isGallery();
    }

    public function create(User $user): bool
    {
        return $user->isGallery();
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->isGallery();
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->isGallery();
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->isGallery();
    }

    public function forceDelete(User $user, Sale $sale): bool
    {
        return $user->isGallery();
    }
}
