<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $t) {
            // Opt-in flag — provenance stays hidden on the public web
            // unless the owner explicitly marks it as public.
            $t->boolean('provenance_is_public')->default(false)->after('provenance');
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $t) {
            $t->dropColumn('provenance_is_public');
        });
    }
};
