<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    // CRM contacts — Gallery & Collector držia vlastnú databázu kontaktov.

    public function viewAny(User $user): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function create(User $user): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->isGallery() || $user->isCollector();
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->isGallery();
    }
}
