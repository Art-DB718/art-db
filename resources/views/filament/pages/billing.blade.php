<x-filament-panels::page>

    {{-- STATUS HERO --}}
    @php
        $statusBadge = match ($user->subscription_status) {
            'trial'      => ['Trial', 'warning'],
            'active'     => ['Active', 'success'],
            'past_due'   => ['Past due — read-only', 'danger'],
            'archived'   => ['Archived', 'danger'],
            'cancelled'  => ['Cancelled', 'gray'],
            default      => [ucfirst($user->subscription_status), 'gray'],
        };
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-800 p-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs uppercase tracking-wider text-gray-500">Current status</p>
                <div class="mt-2 flex items-center gap-3">
                    <span @class([
                        'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                        'bg-amber-100 text-amber-800' => $statusBadge[1] === 'warning',
                        'bg-emerald-100 text-emerald-800' => $statusBadge[1] === 'success',
                        'bg-rose-100 text-rose-800' => $statusBadge[1] === 'danger',
                        'bg-gray-100 text-gray-800' => $statusBadge[1] === 'gray',
                    ])>
                        {{ $statusBadge[0] }}
                    </span>
                    @if ($currentPlan)
                        <span class="text-lg font-medium">{{ $currentPlan['label'] }}</span>
                    @endif
                </div>
                @if ($user->isOnTrial() && $trialDaysLeft !== null)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        Trial ends in <strong>{{ $trialDaysLeft }} {{ Str::plural('day', $trialDaysLeft) }}</strong>
                        ({{ $user->trial_ends_at->format('d. m. Y') }}).
                    </p>
                @elseif ($user->isReadOnly())
                    <p class="text-sm text-rose-700 dark:text-rose-400 mt-2">
                        Your admin is read-only until you re-subscribe. Existing data is safe.
                    </p>
                @endif
            </div>
            <div class="text-sm text-gray-500">
                <p>Signed in as <strong>{{ $user->email }}</strong></p>
                <p>Role: <strong>{{ $user->role->label() }}</strong></p>
            </div>
        </div>
    </div>

    @unless ($isStripeReady)
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-medium mb-1">Stripe not configured yet</p>
            <p>
                Set <code>STRIPE_KEY</code>, <code>STRIPE_SECRET</code> and the
                <code>STRIPE_PRICE_*</code> price IDs in your <code>.env</code>, then create
                matching products in Stripe Dashboard. Until then the upgrade buttons below
                are display-only.
            </p>
        </div>
    @endunless

    {{-- PLAN GRID --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($plans as $key => $plan)
            <div @class([
                'rounded-xl border bg-white dark:bg-gray-900 p-6',
                'border-primary-500 ring-2 ring-primary-500' => $currentPlanKey === $key,
                'border-gray-200 dark:border-gray-800' => $currentPlanKey !== $key,
            ])>
                <p class="text-xs uppercase tracking-wider text-gray-500">{{ $plan['label'] }}</p>
                <p class="mt-1 text-3xl font-medium">
                    €{{ $plan['price_eur'] }}<span class="text-sm text-gray-500 font-normal">/mo</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">€{{ $plan['price_eur_yr'] ?? '—' }} yearly</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">{{ $plan['description'] }}</p>
                <ul class="mt-4 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($plan['limits'] as $limit => $value)
                        <li>
                            <span class="text-gray-400">{{ ucfirst(str_replace('_', ' ', $limit)) }}:</span>
                            <strong>{{ $value === null ? 'Unlimited' : $value }}</strong>
                        </li>
                    @endforeach
                </ul>
                <button
                    type="button"
                    disabled
                    title="{{ $isStripeReady ? '' : 'Configure Stripe first' }}"
                    @class([
                        'mt-5 w-full px-4 py-2 rounded-md text-sm font-medium transition',
                        'bg-primary-600 text-white' => $currentPlanKey !== $key,
                        'bg-gray-100 text-gray-500 cursor-not-allowed' => $currentPlanKey === $key,
                    ])
                >
                    {{ $currentPlanKey === $key ? 'Current plan' : 'Upgrade →' }}
                </button>
            </div>
        @endforeach
    </div>

    <div class="mt-6 text-xs text-gray-500">
        Need more? <strong>Enterprise</strong> — custom multi-gallery, unlimited storage, SLA.
        <a href="mailto:{{ config('mail.from.address') }}" class="underline">Contact us</a>.
    </div>

</x-filament-panels::page>
