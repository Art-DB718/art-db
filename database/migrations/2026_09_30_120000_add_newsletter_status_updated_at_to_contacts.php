<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $t) {
            // When the contact last clicked subscribe/unsubscribe from an
            // email. NULL = they never used the link.
            $t->timestamp('newsletter_status_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $t) {
            $t->dropColumn('newsletter_status_updated_at');
        });
    }
};
