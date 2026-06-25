<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained('artworks')->cascadeOnDelete();
            $table->date('restoration_date')->nullable();
            $table->date('restoration_returned_at')->nullable();
            $table->text('restoration_notes')->nullable();
            $table->decimal('restoration_price', 12, 2)->nullable();
            $table->string('restorer_name')->nullable();
            $table->string('restorer_email')->nullable();
            $table->string('restorer_phone')->nullable();
            $table->jsonb('documents')->nullable();
            $table->jsonb('photos')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['artwork_id', 'position']);
        });

        // Migrate existing flat restoration_* data on artworks → first maintenance row.
        $rows = DB::table('artworks')
            ->whereNotNull('restoration_date')
            ->orWhereNotNull('restoration_notes')
            ->orWhereNotNull('restoration_price')
            ->orWhereNotNull('restorer_name')
            ->orWhereNotNull('restorer_email')
            ->orWhereNotNull('restorer_phone')
            ->orWhereRaw("restoration_documents IS NOT NULL AND restoration_documents::text != '[]'")
            ->orWhereRaw("restoration_photos IS NOT NULL AND restoration_photos::text != '[]'")
            ->get([
                'id',
                'restoration_date',
                'restoration_returned_at',
                'restoration_notes',
                'restoration_price',
                'restorer_name',
                'restorer_email',
                'restorer_phone',
                'restoration_documents',
                'restoration_photos',
            ]);

        $now = now();
        foreach ($rows as $row) {
            DB::table('maintenances')->insert([
                'artwork_id'              => $row->id,
                'restoration_date'        => $row->restoration_date,
                'restoration_returned_at' => $row->restoration_returned_at,
                'restoration_notes'       => $row->restoration_notes,
                'restoration_price'       => $row->restoration_price,
                'restorer_name'           => $row->restorer_name,
                'restorer_email'          => $row->restorer_email,
                'restorer_phone'          => $row->restorer_phone,
                'documents'               => $row->restoration_documents,
                'photos'                  => $row->restoration_photos,
                'position'                => 0,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
