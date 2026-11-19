<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SNG-style Condition Report per artwork — one JSON blob covering:
 *   - which exhibition the report is being written for
 *   - 26 damage checkboxes for the work body + 6 for the frame
 *   - installation / transport notes
 *   - free-text condition description + handing-over signatures
 *
 * JSON (vs separate columns) because the shape evolves and the data
 * is write-mostly: nothing in the app queries individual flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->json('condition_report')->nullable()->after('damage_notes');
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn('condition_report');
        });
    }
};
