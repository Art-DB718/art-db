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
