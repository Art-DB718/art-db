<?php

namespace App\Filament\Widgets;

use App\Services\PlanLimits;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanUsageWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -10; // pin to top of dashboard

    protected function getStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }
        // Admin doesn't have a billing plan; hide the widget for them.
        if ($user->isAdmin()) {
            return [];
        }

        $limits = app(PlanLimits::class);
        $planKey = $user->subscription_plan ?: ($user->subscription_status === 'trial' ? 'trial' : '—');
        $planLabel = config("subscription.plans.{$planKey}.label", ucfirst($planKey));

        $stats = [
            $this->buildStat('Artworks', PlanLimits::ARTWORKS, $user, $limits),
        ];

        // Only show the Artists stat for roles where it's meaningful
        // (Gallery + Collector); Artist users always have themselves = 1.
        if (in_array($user->role?->value, ['gallery', 'collector'], true)) {
            $stats[] = $this->buildStat('Artists', PlanLimits::ARTISTS, $user, $limits);
        }

        $stats[] = $this->buildStat('Storage', PlanLimits::STORAGE, $user, $limits);

        $stats[] = Stat::make('Current plan', $planLabel)
            ->description($this->planDescription($user))
            ->descriptionIcon('heroicon-m-credit-card')
            ->color('gray')
            ->url(url('/admin/billing'));

        return $stats;
    }

    protected function buildStat(string $label, string $resource, $user, PlanLimits $limits): Stat
    {
        $used  = $limits->usage($user, $resource);
        $cap   = $limits->limit($user, $resource);
        $isStorage = $resource === PlanLimits::STORAGE;
        $fmt = fn ($v) => $isStorage ? number_format((float) $v, 2).' GB' : (string) (int) $v;

        if ($cap === null) {
            return Stat::make($label, $fmt($used))
                ->description('Unlimited on your plan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        }

        $pct = $cap > 0 ? min(100, (int) round((((float) $used) / $cap) * 100)) : 0;

        $color = match (true) {
            $pct >= 100 => 'danger',
            $pct >= 80  => 'warning',
            default     => 'success',
        };

        $capDisplay = $isStorage ? ($cap.' GB') : (string) $cap;
        return Stat::make($label, $fmt($used).' / '.$capDisplay)
            ->description($pct.'% used'.($pct >= 80 ? ' — consider upgrading' : ''))
            ->descriptionIcon($pct >= 80 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-chart-bar')
            ->color($color)
            ->url(url('/admin/billing'));
    }

    protected function planDescription($user): string
    {
        return match ($user->subscription_status) {
            'trial'     => 'Trial — '.($user->trialDaysLeft() ?? 0).' day(s) left',
            'active'    => 'Active subscription',
            'past_due'  => 'Past due — upgrade to restore write access',
            'archived'  => 'Archived — read-only',
            'cancelled' => 'Cancelled — pick a plan to restart',
            default     => 'Manage in Billing',
        };
    }
}
