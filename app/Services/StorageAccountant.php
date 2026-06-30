<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Per-user cumulative file-storage accounting. Walks the image columns on
 * Artwork / Artist / Gallery (owned by a user), sums file sizes from the
 * configured disk, and caches the total on users.storage_used_bytes.
 *
 * Called from model observers (Artwork/Artist/Gallery saved/deleted) whenever
 * an image field changes — so the counter stays in sync without a full scan.
 * One-shot rebuilds use the storage:recompute artisan command.
 */
class StorageAccountant
{
    /** Disk that holds uploaded images (matches FileUpload->disk('public')). */
    protected string $disk = 'public';

    /**
     * Recompute the cached storage_used_bytes for a single user from disk.
     * Returns the new total in bytes (also persisted to the user row).
     */
    public function recomputeForUser(User $user): int
    {
        $paths = collect();

        // Artworks owned by this user
        Artwork::query()
            ->where('owner_user_id', $user->id)
            ->get(['primary_image', 'gallery_images'])
            ->each(function ($a) use ($paths) {
                if ($a->primary_image)            $paths->push($a->primary_image);
                if (is_array($a->gallery_images)) $paths = $paths->merge($a->gallery_images);
            });

        // Artists owned by this user (Artist role's own profile, Collector's archive)
        Artist::query()
            ->where('owner_user_id', $user->id)
            ->get(['profile_image', 'cover_image'])
            ->each(function ($a) use ($paths) {
                if ($a->profile_image) $paths->push($a->profile_image);
                if ($a->cover_image)   $paths->push($a->cover_image);
            });

        // Galleries owned by this user (Gallery role has 1; multi-gallery TBD)
        Gallery::query()
            ->where('owner_user_id', $user->id)
            ->get(['logo', 'cover_image'])
            ->each(function ($g) use ($paths) {
                if ($g->logo)        $paths->push($g->logo);
                if ($g->cover_image) $paths->push($g->cover_image);
            });

        $bytes = $this->sumSizes($paths->filter()->unique());

        $user->forceFill(['storage_used_bytes' => $bytes])->saveQuietly();

        return $bytes;
    }

    /**
     * Quick re-sync called from observers — wraps recomputeForUser but only
     * if the model has an owner; silently no-ops otherwise (system / CLI).
     */
    public function syncFromModel($model): void
    {
        $owner = $this->resolveOwner($model);
        if ($owner) {
            $this->recomputeForUser($owner);
        }
    }

    /**
     * Sum file sizes on disk; missing files are treated as 0 (they may have
     * been removed by an admin or never finished uploading).
     */
    protected function sumSizes(iterable $paths): int
    {
        $disk  = Storage::disk($this->disk);
        $total = 0;

        foreach ($paths as $path) {
            if (! $path || ! $disk->exists($path)) {
                continue;
            }
            try {
                $total += (int) $disk->size($path);
            } catch (\Throwable) {
                // Skip unreadable files rather than crashing the whole sync.
            }
        }
        return $total;
    }

    protected function resolveOwner($model): ?User
    {
        return $model->owner ?? null;
    }
}
