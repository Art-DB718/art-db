<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_private_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_room_id')->constrained('private_rooms')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('status')->default('pending');   // pending | sent | viewed
            $table->timestamp('sent_at')->nullable();        // kedy bol odoslaný odkaz tomuto príjemcovi
            $table->timestamps();

            $table->unique(['private_room_id', 'contact_id']);
            $table->index(['private_room_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('contact_private_room'); }
};
