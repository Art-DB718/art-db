<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('artwork_id')->nullable()->constrained('artworks')->nullOnDelete();
            $table->string('description');                 // pre prípad mazania diela zostáva text
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['sale_id', 'position']);
        });
    }

    public function down(): void { Schema::dropIfExists('sale_line_items'); }
};
