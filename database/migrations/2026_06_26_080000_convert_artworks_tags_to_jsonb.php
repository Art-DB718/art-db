<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Postgres `json` lacks an equality operator, which breaks any
     * SELECT DISTINCT artworks.* (Filament BelongsToMany pickers do that).
     * Convert to `jsonb` — same data shape, full operator support.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE artworks ALTER COLUMN tags TYPE jsonb USING tags::jsonb');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE artworks ALTER COLUMN tags TYPE json USING tags::json');
    }
};
