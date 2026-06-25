<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            // Visible damage / condition info — shown on Basic tab for quick reference.
            // Distinct from `condition_notes` (broader documentation in History tab).
            $table->text('damage_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn('damage_notes');
        });
    }
};
