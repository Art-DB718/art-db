<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RemindTrials extends Command
{
    protected $signature   = 'subscriptions:remind-trials';
    protected $description = 'Email trial users when they have 7 / 3 / 1 days left, once per milestone.';

    /**
     * Days-left milestones we want to notify about. Each is sent at most
     * once per user (tracked in users.trial_reminders_sent).
     */
    protected array $milestones = [7, 3, 1];

    public function handle(): int
    {
        $sent   = 0;
        $errors = 0;

        $users = User::query()
            ->where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now())
            ->get();

        foreach ($users as $user) {
            // Truncate toward zero — at each daily run trial_ends_at sits
            // somewhere between X.0 and X+1.0 days away, so floor gives the
            // canonical 'X days left' bucket. Ensures each milestone hits
            // exactly one daily run.
            $daysLeft = (int) now()->diffInDays($user->trial_ends_at, absolute: false);
            if ($daysLeft <= 0) {
                continue;
            }

            // Find the highest milestone we hit today AND haven't fired yet.
            $alreadySent = array_map('intval', $user->trial_reminders_sent ?? []);
            $milestone   = null;
            foreach ($this->milestones as $m) {
                if ($daysLeft === $m && ! in_array($m, $alreadySent, true)) {
                    $milestone = $m;
                    break;
                }
            }

            if ($milestone === null) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new TrialEndingReminder($user, $milestone));
                $user->forceFill([
                    'trial_reminders_sent' => array_values(array_unique([...$alreadySent, $milestone])),
                ])->save();
                $sent++;
                $this->info("Reminder sent: {$user->email} ({$milestone}d left)");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Failed for {$user->email}: ".$e->getMessage());
                logger()->warning('Trial reminder failed for '.$user->email.': '.$e->getMessage());
            }
        }

        $this->info("Reminders sent: {$sent}");
        if ($errors) {
            $this->warn("Errors: {$errors}");
        }

        return self::SUCCESS;
    }
}
