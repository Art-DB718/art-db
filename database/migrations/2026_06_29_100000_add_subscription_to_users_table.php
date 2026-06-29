<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * SaaS subscription tracking on users:
     *  - subscription_plan: collector_free | starter | pro | studio | enterprise
     *  - subscription_status: trial | active | past_due | archived | cancelled
     *  - trial_ends_at: when the 14-day trial expires (NULL for Collector Free)
     *
     * Existing users get 'trial' with trial_ends_at = now + 14 days via the
     * accompanying console command (backfill). Collectors get 'collector_free'.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan', 32)->nullable()->index();
            $table->string('subscription_status', 16)->default('trial')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_status',
                'trial_ends_at',
                'subscription_expires_at',
            ]);
        });
    }
};
