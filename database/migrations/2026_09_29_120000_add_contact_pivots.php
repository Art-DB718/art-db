<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contact ↔ ContactGroup (many-to-many). Backwards compatible —
        // contacts.group_id stays for existing code, but the pivot lets a
        // contact belong to multiple groups (matches artgalleria's model).
        Schema::create('contact_contact_group', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['contact_id', 'contact_group_id'], 'contact_group_unique');
        });

        // Contact ↔ Artist "interests" — which artists a contact follows /
        // is interested in. Replaces the free-text `contacts.interests`
        // jsonb array for gallery use (that array stays around for legacy).
        Schema::create('contact_artist_interest', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['contact_id', 'artist_id'], 'contact_artist_unique');
        });

        // Backfill: every existing contacts.group_id → pivot row so the
        // Filament form (which will read from the pivot) shows the
        // current group as pre-selected.
        DB::statement(<<<'SQL'
            INSERT INTO contact_contact_group (contact_id, contact_group_id, created_at, updated_at)
            SELECT id, group_id, NOW(), NOW()
            FROM contacts
            WHERE group_id IS NOT NULL
            ON CONFLICT (contact_id, contact_group_id) DO NOTHING
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_artist_interest');
        Schema::dropIfExists('contact_contact_group');
    }
};
