<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exhibitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('group');     // solo | group | art_fair | online | museum
            $table->string('venue')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('opening_at')->nullable();
            $table->string('curator')->nullable();
            $table->longText('description')->nullable();
            $table->longText('press_release')->nullable();
            $table->string('poster_image')->nullable();
            $table->jsonb('gallery_images')->nullable();
            $table->jsonb('documents')->nullable();       // PDF press kits, install shots manifests
            $table->string('status')->default('upcoming'); // upcoming | current | past | cancelled
            $table->boolean('is_published')->default(false);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void { Schema::dropIfExists('exhibitions'); }
};
