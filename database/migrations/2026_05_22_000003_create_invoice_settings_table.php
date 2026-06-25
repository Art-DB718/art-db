<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();

            $table->string('logo_path')->nullable();
            $table->string('company_name')->nullable();

            $table->string('business_id')->nullable();  // IČO
            $table->string('tax_id')->nullable();        // DIČ
            $table->string('vat_id')->nullable();        // IČ DPH

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            $table->string('bank_account')->nullable();  // IBAN
            $table->string('bank_name')->nullable();

            $table->text('footer_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('invoice_settings'); }
};
