<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('slug')->unique();
            $table->integer('birth_year')->nullable();
            $table->integer('death_year')->nullable();
            $table->string('birth_place')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->text('short_bio')->nullable();
            $table->longText('biography')->nullable();
            $table->longText('statement')->nullable();
            $table->string('website')->nullable();
            $table->jsonb('social_links')->nullable();   // {instagram, facebook, twitter}
            $table->string('profile_image')->nullable(); // cesta k súboru
            $table->string('cover_image')->nullable();
            $table->string('branding_theme')->default('default');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('last_name');
            $table->index('is_published');
        });
    }

    public function down(): void { Schema::dropIfExists('artists'); }
};
