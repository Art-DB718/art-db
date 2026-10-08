<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Tenant-scoping for Filament resources whose model carries
 * owner_user_id. Admins see every row; everyone else sees only
 * rows they own. Soft-deleted rows are included so the Trashed
 * filter works.
 *
 * Usage: `use OwnerScoped;` in the Resource class. The trait
 * replaces the default `getEloquentQuery()`. If a resource needs
 * extra joins or custom logic, override and call `scopeToOwner()`
 * on the result instead.
 */
trait OwnerScoped
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        return self::scopeToOwner($query);
    }

    protected static function scopeToOwner(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where('owner_user_id', $user->id);
    }
}
