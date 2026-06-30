{{--
    Shared role-page layout. Pass:
    - $role         string  (gallery|artist|collector)
    - $eyebrow      string  ("For galleries", …)
    - $title        string  ("Gallery", …)
    - $intro        string  long-form pitch paragraph
    - $features     array   [['title'=>…, 'body'=>…], …]
    - $cta          string  text on the bottom CTA button
--}}
<x-layouts.public
    :title="$title.' — Platform features — '.config('app.name', 'ArtDB')"
    :description="$intro">

    {{-- BREADCRUMB --}}
    <section class="border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-6">
            <nav class="text-xs uppercase tracking-[0.18em] text-gray-500">
                <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('platform') }}" class="hover:text-gray-900">Platform</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $title }}</span>
            </nav>
        </div>
    </section>

    {{-- HERO --}}
    <section class="bg-gradient-to-b from-gray-50 to-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6 py-28 md:py-36">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-8">{{ $eyebrow }}</p>
            <h1 class="font-serif text-5xl md:text-7xl tracking-tight leading-[1.1] mb-10">
                {{ $title }}<span class="italic text-gray-700">.</span>
            </h1>
            <p class="text-base md:text-lg text-gray-700 leading-loose max-w-3xl">
                {{ $intro }}
            </p>
        </div>
    </section>

    {{-- FEATURES --}}
    <section class="py-28 md:py-32">
        <div class="max-w-5xl mx-auto px-6">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-5">What you get</p>
            <h2 class="font-serif text-3xl md:text-4xl mb-20">In detail.</h2>

            <div class="space-y-16">
                @foreach ($features as $i => $f)
                    <div class="grid grid-cols-1 md:grid-cols-[80px_1fr] gap-6 md:gap-10 items-start">
                        <div class="font-serif text-3xl text-gray-300 leading-none pt-1">
                            {{ str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <div>
                            <h3 class="font-serif text-2xl mb-4">{{ $f['title'] }}</h3>
                            <p class="text-gray-700 leading-loose">{{ $f['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PRICING --}}
    @php
        $allPlans = config('subscription.plans', []);
        // Per-role plan order: Collector sees its free plan first, then upgrade options.
        $planKeys = $role === 'collector'
            ? ['collector_free', 'starter', 'pro']
            : ['starter', 'pro', 'studio'];

        // Per-role limit keys actually meaningful to that role.
        //   - Artist: has 1 fixed profile (themselves), doesn't manage galleries → only artworks + storage matter
        //   - Collector: private archive of artists + artworks
        //   - Gallery: artists + artworks + storage. (Multi-gallery is not in
        //     the data model yet — every Gallery user owns exactly one Gallery
        //     via User::gallery() — so the per-plan 'galleries' cap is hidden
        //     here until multi-gallery support ships.)
        $relevantLimitKeys = match ($role) {
            'artist'    => ['artworks', 'storage_gb'],
            'collector' => ['artists', 'artworks', 'storage_gb'],
            default     => ['artists', 'artworks', 'storage_gb'],
        };
        // Human-friendly labels (override the default ucfirst+underscore→space).
        $limitLabels = [
            'galleries'  => 'Galleries',
            'artists'    => $role === 'collector' ? 'Private artists' : 'Represented artists',
            'artworks'   => $role === 'collector' ? 'Private artworks' : 'Artworks',
            'storage_gb' => 'Storage',
        ];
    @endphp
    <section class="py-24 bg-gray-50 border-t border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-3">Pricing</p>
                <h2 class="font-serif text-3xl md:text-4xl mb-4">Simple plans, no surprises.</h2>
                <p class="text-gray-600 leading-relaxed">
                    14-day full-feature trial for every new account. Annual billing is 10 months — two months free.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($planKeys as $key)
                    @php $plan = $allPlans[$key] ?? null; @endphp
                    @if ($plan)
                        <div class="bg-white border border-gray-200 p-8 flex flex-col h-full">
                            {{-- Header (fixed-height block so prices align) --}}
                            <div class="min-h-[140px]">
                                <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-3">{{ $plan['label'] }}</p>
                                @if (($plan['price_eur'] ?? 0) === 0)
                                    <p class="font-serif text-4xl leading-none">Free</p>
                                    <p class="text-xs text-gray-500 mt-2">{{ $key === 'collector_free' ? 'Forever — no card needed' : '14-day trial' }}</p>
                                @else
                                    <p class="font-serif text-4xl leading-none">€{{ $plan['price_eur'] }}<span class="text-sm text-gray-500 font-normal"> /mo</span></p>
                                    @if (! empty($plan['price_eur_yr']))
                                        <p class="text-xs text-gray-500 mt-2">or €{{ $plan['price_eur_yr'] }} / year (2 months free)</p>
                                    @endif
                                @endif
                            </div>

                            <p class="text-sm text-gray-700 leading-relaxed border-t border-gray-100 pt-5 mb-5">
                                {{ $plan['description'] }}
                            </p>

                            <ul class="space-y-2 text-sm text-gray-700 mb-6 flex-grow">
                                @foreach ($plan['limits'] as $limit => $value)
                                    @continue (! in_array($limit, $relevantLimitKeys, true))
                                    <li class="flex justify-between gap-3 border-b border-gray-50 pb-2">
                                        <span class="text-gray-500">{{ $limitLabels[$limit] ?? ucfirst(str_replace('_', ' ', $limit)) }}</span>
                                        <span class="font-medium text-gray-900">
                                            {{ $value === null ? 'Unlimited' : ($limit === 'storage_gb' ? $value.' GB' : $value) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            @auth
                                <a href="{{ url('/admin/billing') }}" class="block text-center px-4 py-2.5 text-xs uppercase tracking-[0.18em] bg-gray-900 text-white hover:bg-gray-700 transition">
                                    Choose in billing
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="block text-center px-4 py-2.5 text-xs uppercase tracking-[0.18em] bg-gray-900 text-white hover:bg-gray-700 transition">
                                    {{ ($plan['price_eur'] ?? 0) === 0 ? 'Sign up free' : 'Start free trial' }}
                                </a>
                            @endauth
                        </div>
                    @endif
                @endforeach
            </div>

            <p class="text-center text-sm text-gray-500 mt-10">
                Need more? <strong>Enterprise</strong> — multi-gallery, museum-scale storage, custom SLA.
                <a href="mailto:{{ optional(\App\Models\InvoiceSetting::current())->email ?: config('mail.from.address') }}" class="underline hover:no-underline">Contact us</a>.
            </p>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-24 bg-gray-900 text-white">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="font-serif text-3xl md:text-4xl mb-8">{{ $cta }}</h2>
            @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-block px-10 py-4 bg-white text-gray-900 text-xs uppercase tracking-[0.18em] hover:bg-gray-200 transition">
                    Open your dashboard
                </a>
            @else
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="{{ route('register') }}"
                       class="px-10 py-4 bg-white text-gray-900 text-xs uppercase tracking-[0.18em] hover:bg-gray-200 transition">
                        Create an account
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-10 py-4 border border-gray-700 text-xs uppercase tracking-[0.18em] hover:border-white transition">
                        Log in
                    </a>
                </div>
            @endauth
        </div>
    </section>
</x-layouts.public>
