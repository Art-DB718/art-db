<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            // CV-style structured arrays — populated via Filament Repeater in Bio & CV tab.
            $table->jsonb('education')->nullable();             // [{institution, city, country, degree, field, year_from, year_to}, ...]
            $table->jsonb('previous_exhibitions')->nullable();  // [{year, title, venue, city, country, type: solo|group}, ...]
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn(['education', 'previous_exhibitions']);
        });
    }
};
