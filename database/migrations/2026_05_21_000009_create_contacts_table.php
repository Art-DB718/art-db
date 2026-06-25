<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('contact_groups')->nullOnDelete();
            $table->jsonb('interests')->nullable();      // ['painting', 'sculpture']
            $table->text('notes')->nullable();
            $table->string('source')->nullable();        // odkiaľ sme ho dostali
            $table->timestamp('last_contact_at')->nullable();
            $table->boolean('subscribed_to_newsletter')->default(false);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('email');
        });
    }

    public function down(): void { Schema::dropIfExists('contacts'); }
};
