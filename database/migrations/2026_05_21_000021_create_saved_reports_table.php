<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type');                       // inventory_valuation | sales | consignments | artist_summary | etc.
            $table->jsonb('filters')->nullable();
            $table->string('output_format')->default('pdf'); // pdf | xlsx | csv | json
            $table->string('schedule')->nullable();       // cron expression for auto-runs
            $table->jsonb('recipients')->nullable();      // email addresses
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('saved_reports'); }
};
