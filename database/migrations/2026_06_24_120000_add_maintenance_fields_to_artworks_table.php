<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            // Maintenance / restoration — info o reštaurovaní diela, cena, kontakt na reštaurátora, prílohy.
            $table->date('restoration_date')->nullable();
            $table->text('restoration_notes')->nullable();
            $table->decimal('restoration_price', 12, 2)->nullable();
            $table->string('restorer_name')->nullable();
            $table->string('restorer_email')->nullable();
            $table->string('restorer_phone')->nullable();
            $table->jsonb('restoration_documents')->nullable();
            $table->jsonb('restoration_photos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn([
                'restoration_date',
                'restoration_notes',
                'restoration_price',
                'restorer_name',
                'restorer_email',
                'restorer_phone',
                'restoration_documents',
                'restoration_photos',
            ]);
        });
    }
};
