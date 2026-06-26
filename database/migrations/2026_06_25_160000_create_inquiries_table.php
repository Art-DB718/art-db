<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Artwork owner_user_id at time of sending; nullable if owner is later removed');
            $table->foreignId('artwork_id')->constrained('artworks')->cascadeOnDelete();
            $table->text('message');
            $table->string('status', 16)->default('new')->index()
                ->comment('new | replied | closed');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'status']);
            $table->index(['sender_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
