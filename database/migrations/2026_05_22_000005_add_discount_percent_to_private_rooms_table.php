<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('private_rooms', function (Blueprint $table) {
            // Globálna zľava (0-100 %) aplikovaná na ceny diel zobrazené klientovi.
            $table->unsignedTinyInteger('discount_percent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('private_rooms', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
