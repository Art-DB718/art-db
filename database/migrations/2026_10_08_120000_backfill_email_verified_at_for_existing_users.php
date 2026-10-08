<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark every existing account as verified now. Without this
        // backfill, enabling MustVerifyEmail would lock out every
        // historical user (Kat included) at the next login because
        // their email_verified_at is NULL.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No-op: we can't tell which rows the up() touched versus which
        // were already verified. Leave them alone.
    }
};
