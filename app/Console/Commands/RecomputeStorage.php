<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StorageAccountant;
use Illuminate\Console\Command;

class RecomputeStorage extends Command
{
    protected $signature   = 'storage:recompute {--user= : Only recompute for this user ID or email}';
    protected $description = 'Walk owned files for each user and refresh users.storage_used_bytes. One-shot backfill.';

    public function handle(StorageAccountant $accountant): int
    {
        $query = User::query();
        if ($id = $this->option('user')) {
            $query->where('id', $id)->orWhere('email', $id);
        }

        $users = $query->get();
        $total = 0;

        foreach ($users as $user) {
            $bytes = $accountant->recomputeForUser($user);
            $mb    = round($bytes / 1024 / 1024, 2);
            $this->line("{$user->email}: {$mb} MB");
            $total += $bytes;
        }

        $this->info('Done — '.$users->count().' user(s), '.round($total / 1024 / 1024, 2).' MB total.');

        return self::SUCCESS;
    }
}
