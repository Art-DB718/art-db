<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('private_room_artwork', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_room_id')->constrained('private_rooms')->cascadeOnDelete();
            $table->foreignId('artwork_id')->constrained('artworks')->cascadeOnDelete();
            $table->decimal('display_price', 12, 2)->nullable();  // override ceny pre tohto klienta
            $table->string('currency', 3)->default('EUR');
            $table->integer('position')->default(0);
            $table->text('private_note')->nullable();
            $table->timestamps();

            $table->unique(['private_room_id', 'artwork_id']);
            $table->index(['private_room_id', 'position']);
        });
    }

    public function down(): void { Schema::dropIfExists('private_room_artwork'); }
};
