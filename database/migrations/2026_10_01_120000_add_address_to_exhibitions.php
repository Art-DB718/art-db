<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exhibitions', function (Blueprint $t) {
            // Full street address (street, number, postal code, city, country)
            // where the exhibition physically takes place. Replaces the
            // location_id dropdown as the primary location field. We keep
            // location_id in place for existing data but new exhibitions
            // should use `address`.
            $t->text('address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exhibitions', function (Blueprint $t) {
            $t->dropColumn('address');
        });
    }
};
