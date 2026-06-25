<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            // NULL = dielo je stále v reštaurátorskej dielni; dátum = už vrátené.
            $table->date('restoration_returned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn('restoration_returned_at');
        });
    }
};
