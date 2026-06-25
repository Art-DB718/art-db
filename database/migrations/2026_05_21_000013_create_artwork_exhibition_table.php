<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artwork_exhibition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained('artworks')->cascadeOnDelete();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->boolean('was_sold')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['artwork_id', 'exhibition_id']);
            $table->index(['exhibition_id', 'position']);
        });
    }

    public function down(): void { Schema::dropIfExists('artwork_exhibition'); }
};
