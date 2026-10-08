<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mediums', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->foreignId('owner_user_id')->nullable()->after('description')
                    ->constrained('users')->nullOnDelete();
                $t->index('owner_user_id');
            });

            // Drop global unique on slug so two tenants can both name a
            // medium "Oil on canvas" without colliding at the DB level.
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_slug_unique");
            } else {
                Schema::table($table, function (Blueprint $t) {
                    try { $t->dropUnique(['slug']); } catch (\Throwable $e) {}
                });
            }

            // Backfill: assign each existing row to the dominant owner of
            // its artworks. Reference rows that no-one uses stay NULL and
            // remain visible only to admins.
            $col = $table === 'mediums' ? 'medium_id' : 'genre_id';
            $rows = DB::table($table)->whereNull('owner_user_id')->get(['id']);
            foreach ($rows as $r) {
                $owner = DB::table('artworks')
                    ->where($col, $r->id)
                    ->selectRaw('owner_user_id, COUNT(*) as c')
                    ->groupBy('owner_user_id')
                    ->orderByDesc('c')
                    ->limit(1)
                    ->value('owner_user_id');

                if ($owner) {
                    DB::table($table)->where('id', $r->id)->update(['owner_user_id' => $owner]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['mediums', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('owner_user_id');
            });
        }
    }
};
