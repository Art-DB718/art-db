<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            // Certificate of Authenticity
            $table->longText('cert_intro')->nullable();              // intro paragraph
            $table->string('cert_signature_label')->nullable();      // label under signature line
            $table->boolean('cert_show_uuid')->default(true);        // show inventory + uuid in footer

            // Artwork Card
            $table->string('card_footer_text')->nullable();          // custom footer text
            $table->boolean('card_show_provenance')->default(false); // show provenance section
            $table->boolean('card_show_price')->default(true);       // show price

            // Artwork Label
            $table->boolean('label_show_price')->default(true);
            $table->boolean('label_show_dimensions')->default(true);
            $table->boolean('label_show_inventory')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cert_intro', 'cert_signature_label', 'cert_show_uuid',
                'card_footer_text', 'card_show_provenance', 'card_show_price',
                'label_show_price', 'label_show_dimensions', 'label_show_inventory',
            ]);
        });
    }
};
