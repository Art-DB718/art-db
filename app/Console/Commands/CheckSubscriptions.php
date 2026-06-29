<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckSubscriptions extends Command
{
    protected $signature   = 'subscriptions:check';
    protected $description = 'Expire trials past their end date; archive past-due users beyond the grace period.';

    public function handle(): int
    {
        $now        = now();
        $graceDays  = (int) config('subscription.grace_days_after_trial', 30);
        $expiredAt  = $now->copy()->subDays($graceDays);

        // 1. Trial users whose trial_ends_at has passed → past_due
        $expiredTrials = User::query()
            ->where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->update(['subscription_status' => 'past_due']);

        // 2. past_due users whose trial ended more than graceDays ago → archived
        $archived = User::query()
            ->where('subscription_status', 'past_due')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $expiredAt)
            ->update(['subscription_status' => 'archived']);

        $this->info("Trials expired: {$expiredTrials}");
        $this->info("Archived (past grace): {$archived}");

        return self::SUCCESS;
    }
}
