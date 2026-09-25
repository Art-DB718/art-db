<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Diacritics-insensitive LIKE for Filament table search.
 *
 * On PostgreSQL uses the `unaccent` extension (see the
 * enable_unaccent_extension migration). On other drivers falls back to a
 * plain case-insensitive LIKE so local SQLite dev keeps working — accents
 * still count there, but this project only really needs it on prod.
 *
 * Relation columns (dot notation, e.g. `artist.last_name`) are matched
 * with whereHas so joins are not required in the base query.
 */
class SearchNormalizer
{
    public static function apply(Builder $query, array $columns, string $search): Builder
    {
        $driver = $query->getConnection()->getDriverName();
        $isPgsql = $driver === 'pgsql';
        $like    = $isPgsql ? 'ILIKE' : 'LIKE';
        $wrapCol = fn (string $col) => $isPgsql
            ? "unaccent(lower({$col}))"
            : "lower({$col})";
        $wrapVal = $isPgsql ? 'unaccent(lower(?))' : 'lower(?)';

        $needle = '%'.$search.'%';

        $query->where(function ($q) use ($columns, $wrapCol, $wrapVal, $like, $needle) {
            foreach ($columns as $col) {
                if (str_contains($col, '.')) {
                    [$relation, $attr] = explode('.', $col, 2);
                    $q->orWhereHas($relation, function ($sub) use ($attr, $wrapCol, $wrapVal, $like, $needle) {
                        $sub->whereRaw(
                            sprintf('%s %s %s', $wrapCol($attr), $like, $wrapVal),
                            [$needle],
                        );
                    });
                } else {
                    $q->orWhereRaw(
                        sprintf('%s %s %s', $wrapCol($col), $like, $wrapVal),
                        [$needle],
                    );
                }
            }
        });

        return $query;
    }
}
