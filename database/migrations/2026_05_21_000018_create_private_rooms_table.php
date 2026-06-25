<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('private_rooms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('token', 64)->unique();          // verejný URL token
            $table->foreignId('recipient_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('recipient_name')->nullable();   // ak nie je v kontaktoch
            $table->string('recipient_email')->nullable();
            $table->longText('welcome_message')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('show_prices')->default(true);
            $table->boolean('allow_inquiry')->default(true);
            $table->string('sort_strategy')->default('manual'); // manual | price_asc | price_desc | year_desc
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void { Schema::dropIfExists('private_rooms'); }
};
