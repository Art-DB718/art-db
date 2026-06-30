<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which trial-end-warning milestones we've already emailed about
            // (e.g. [7, 3, 1]). subscriptions:remind-trials skips any
            // milestone already in this list so the user gets each reminder
            // exactly once even if the cron double-fires.
            $table->jsonb('trial_reminders_sent')->default('[]');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trial_reminders_sent');
        });
    }
};
