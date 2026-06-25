<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artwork_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');         // For Sale, Reserved, Sold, NFS, On Loan, Archived...
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#999999'); // hex farba pre admin badge
            $table->boolean('is_public')->default(true);    // zobrazovať vo verejnom liste?
            $table->boolean('counts_as_available')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('artwork_statuses'); }
};
