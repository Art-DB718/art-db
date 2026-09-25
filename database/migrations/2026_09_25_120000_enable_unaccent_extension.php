<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Diacritics-insensitive table search relies on Postgres' `unaccent`.
        // On SQLite / MySQL the accent-insensitive path is skipped in
        // App\Support\SearchNormalizer, so we only need the extension on prod.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        }
    }

    public function down(): void
    {
        // Leave the extension in place — it's harmless and other features
        // may come to rely on it.
    }
};
