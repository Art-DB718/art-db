<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Allow inquiries from unauthenticated visitors on the public site:
     *  - sender_user_id may be NULL
     *  - guest_name / guest_email capture identity when no account exists
     */
    public function up(): void
    {
        // Drop the existing FK so we can change nullability cleanly.
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['sender_user_id']);
        });

        DB::statement('ALTER TABLE inquiries ALTER COLUMN sender_user_id DROP NOT NULL');

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('guest_name')->nullable()->after('sender_user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_email']);
            $table->dropForeign(['sender_user_id']);
        });

        DB::statement('ALTER TABLE inquiries ALTER COLUMN sender_user_id SET NOT NULL');

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreign('sender_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
