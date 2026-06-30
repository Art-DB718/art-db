<x-mail::message>
# Your trial ends @if ($daysLeft <= 1) tomorrow @else in {{ $daysLeft }} days @endif

Hi {{ trim(explode(' ', $user->name)[0] ?: 'there') }},

Your **{{ $appName }}** 14-day trial wraps up
@if ($user->trial_ends_at)
on **{{ $user->trial_ends_at->format('l, j F Y') }}**.
@endif
@if ($daysLeft <= 1)
That's tomorrow — after that, your workspace switches to read-only until you pick a plan.
@elseif ($daysLeft <= 3)
That's in just {{ $daysLeft }} days. Once it ends, your workspace becomes read-only until you choose a plan.
@else
That gives you {{ $daysLeft }} days to keep exploring. After your trial ends, your workspace switches to read-only until you pick a plan.
@endif

Pick a plan that fits — pricing starts at **€9 / month**:

- **Starter** — €9/mo · for solo practice or a small gallery
- **Professional** — €29/mo · growing roster or serious collector
- **Studio** — €79/mo · larger archives, institutions

All plans are month-to-month, no commitment, cancel any time.
Annual billing saves you two months.

<x-mail::button :url="$billingUrl">
Choose your plan
</x-mail::button>

Questions? Just reply to this email — we read every message.

Thanks,
{{ $appName }}
</x-mail::message>
