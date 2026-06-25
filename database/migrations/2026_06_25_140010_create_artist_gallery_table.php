<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artist_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->date('represented_since')->nullable();
            $table->boolean('is_primary')->default(false)
                ->comment('Primary representing gallery for an artist (max 1 per artist)');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['gallery_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_gallery');
    }
};
