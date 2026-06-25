<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            // Editovateľné HTML telo pozývacieho emailu (s placeholdermi).
            $table->longText('invitation_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $table) {
            $table->dropColumn('invitation_message');
        });
    }
};
