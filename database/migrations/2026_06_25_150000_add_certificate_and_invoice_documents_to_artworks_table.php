<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            // Scanned / received documents — typically uploaded by Gallery (issued)
            // or Collector (archived on acquisition).
            $table->string('certificate_of_authenticity_document')->nullable();
            $table->string('invoice_document')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn(['certificate_of_authenticity_document', 'invoice_document']);
        });
    }
};
