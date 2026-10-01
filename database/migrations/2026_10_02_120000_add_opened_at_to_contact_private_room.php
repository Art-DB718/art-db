<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_private_room', function (Blueprint $t) {
            // First time this recipient's email was tracked as opened
            // (tracking pixel loaded or the private-room link was clicked).
            $t->timestamp('opened_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contact_private_room', function (Blueprint $t) {
            $t->dropColumn('opened_at');
        });
    }
};
