<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_exhibition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exhibition_id')->constrained('exhibitions')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('status')->default('pending');   // pending | sent | accepted | declined
            $table->timestamp('sent_at')->nullable();        // kedy bola pozvánka odoslaná
            $table->timestamps();

            $table->unique(['exhibition_id', 'contact_id']);
            $table->index(['exhibition_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('contact_exhibition'); }
};
