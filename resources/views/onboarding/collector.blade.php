<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Onboarding</p>
        <h1 class="font-serif text-3xl">Welcome, collector</h1>
        <p class="text-sm text-gray-500 mt-2">Set a display name — you can change it anytime.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.collector') }}">
        @csrf

        <div>
            <x-input-label for="display_name" :value="__('Display name')" />
            <x-text-input id="display_name" class="block mt-1 w-full" type="text" name="display_name" :value="old('display_name', auth()->user()->name)" required autofocus />
            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>{{ __('Finish setup') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
