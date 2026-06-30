<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cached cumulative size (bytes) of all files this user owns
            // — Artwork primary_image + gallery_images, Artist profile/cover,
            // Gallery logo/cover. Recomputed by StorageAccountant on any
            // saved/deleted event touching an image field. Read by
            // PlanLimits::usage('storage_gb') + PlanUsageWidget.
            $table->bigInteger('storage_used_bytes')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('storage_used_bytes');
        });
    }
};
