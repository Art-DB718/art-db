<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_groups', function (Blueprint $t) {
            $t->foreignId('owner_user_id')->nullable()->after('description')
                ->constrained('users')->nullOnDelete();
            $t->index('owner_user_id');
        });

        // Global unique on `slug` would prevent two tenants from both
        // naming a group "Buyers" / "Collectors". Drop it; slug stays as
        // a convenience but is no longer uniqueness-enforced.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE contact_groups DROP CONSTRAINT IF EXISTS contact_groups_slug_unique');
        } else {
            Schema::table('contact_groups', function (Blueprint $t) {
                $t->dropUnique(['slug']);
            });
        }

        // Backfill: assign each existing group to the dominant owner of
        // its attached contacts. Groups with no attachments stay NULL and
        // remain visible only to admins — Kat can reassign manually.
        $groups = DB::table('contact_groups')->whereNull('owner_user_id')->get(['id']);
        foreach ($groups as $g) {
            $owner = DB::table('contact_contact_group as pivot')
                ->join('contacts', 'contacts.id', '=', 'pivot.contact_id')
                ->where('pivot.contact_group_id', $g->id)
                ->selectRaw('contacts.owner_user_id, COUNT(*) as c')
                ->groupBy('contacts.owner_user_id')
                ->orderByDesc('c')
                ->limit(1)
                ->value('owner_user_id');

            if ($owner) {
                DB::table('contact_groups')->where('id', $g->id)->update(['owner_user_id' => $owner]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('contact_groups', function (Blueprint $t) {
            $t->dropConstrainedForeignId('owner_user_id');
        });
    }
};
