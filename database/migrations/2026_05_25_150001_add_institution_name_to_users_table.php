<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('institution_name')->nullable()->after('name');
            $table->string('institution_city')->nullable()->after('institution_name');
            $table->string('institution_country')->nullable()->after('institution_city');
            $table->string('institution_website')->nullable()->after('institution_country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['institution_name', 'institution_city', 'institution_country', 'institution_website']);
        });
    }
};
