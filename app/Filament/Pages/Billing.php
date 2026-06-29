<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class Billing extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Billing';
    protected static ?string $title           = 'Billing';
    protected static ?int    $navigationSort  = 99;

    protected static string $view = 'filament.pages.billing';

    public static function canAccess(): bool
    {
        return (bool) auth()->check();
    }

    public function getViewData(): array
    {
        $user  = auth()->user();
        $plans = config('subscription.plans', []);
        $current = $user->subscription_plan ?: ($user->subscription_status === 'trial' ? 'trial' : null);

        return [
            'user'           => $user,
            'currentPlanKey' => $current,
            'currentPlan'    => $plans[$current] ?? null,
            'plans'          => collect($plans)
                ->except(['trial', 'collector_free', 'enterprise'])
                ->all(),
            'trialDaysLeft'  => $user->trialDaysLeft(),
            'isStripeReady'  => filled(config('cashier.secret')),
        ];
    }
}
