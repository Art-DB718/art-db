<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mediums', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                // Owner-provided English translation of `name`. When
                // filled, the public web prefers it over the auto-
                // translated dictionary pass of the original name.
                $t->string('name_en')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        foreach (['mediums', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('name_en');
            });
        }
    }
};
