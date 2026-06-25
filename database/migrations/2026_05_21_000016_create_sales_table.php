<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();        // INV-2026-0001
            $table->foreignId('buyer_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->string('payment_status')->default('draft'); // draft | sent | partial | paid | overdue | cancelled
            $table->string('payment_method')->nullable();       // bank_transfer | card | cash | stripe
            $table->string('currency', 3)->default('EUR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->jsonb('billing_address')->nullable();
            $table->jsonb('shipping_address')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_status');
            $table->index('sale_date');
        });
    }

    public function down(): void { Schema::dropIfExists('sales'); }
};
