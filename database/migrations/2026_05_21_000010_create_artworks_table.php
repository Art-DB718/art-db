<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('inventory_id')->unique();             // INV-SIKR-0001
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('year_created')->nullable();
            $table->integer('year_created_end')->nullable();      // pre rozsahy "1985–1987"
            $table->foreignId('medium_id')->nullable()->constrained('mediums')->nullOnDelete();
            $table->foreignId('genre_id')->nullable()->constrained('genres')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('artwork_statuses')->nullOnDelete();
            $table->longText('description')->nullable();
            $table->text('materials')->nullable();                // "Oil on canvas, mixed media"

            // Rozmery
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('depth_cm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();

            // Edícia
            $table->integer('edition_number')->nullable();
            $table->integer('edition_total')->nullable();
            $table->string('edition_notes')->nullable();          // "AP", "1/10 + 2 AP"

            // Signácia
            $table->boolean('is_signed')->default(false);
            $table->boolean('is_dated')->default(false);
            $table->boolean('is_framed')->default(false);
            $table->text('signature_description')->nullable();

            // Cena
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('price_on_request')->default(false);

            // Stav, dokumenty, provenance
            $table->text('condition_notes')->nullable();
            $table->longText('provenance')->nullable();
            $table->longText('exhibition_history')->nullable();
            $table->longText('literature')->nullable();
            $table->boolean('has_certificate_of_authenticity')->default(false);

            // Lokalita (kde fyzicky je)
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Obrázky — primárny + JSON galéria
            $table->string('primary_image')->nullable();
            $table->jsonb('gallery_images')->nullable();

            // Verejnosť
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);

            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('artist_id');
            $table->index('status_id');
            $table->index('is_published');
            $table->index('inventory_id');
        });
    }

    public function down(): void { Schema::dropIfExists('artworks'); }
};
