<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            // Frame dimensions — separate from artwork dimensions (canvas / image size).
            $table->decimal('frame_height_cm', 8, 2)->nullable();
            $table->decimal('frame_width_cm', 8, 2)->nullable();
            $table->decimal('frame_depth_cm', 8, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn(['frame_height_cm', 'frame_width_cm', 'frame_depth_cm']);
        });
    }
};
