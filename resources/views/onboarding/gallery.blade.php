<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Onboarding</p>
        <h1 class="font-serif text-3xl">Tell us about your institution</h1>
        <p class="text-sm text-gray-500 mt-2">You can edit any of this later from your dashboard.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.gallery') }}">
        @csrf

        <div>
            <x-input-label for="gallery_name" :value="__('Institution / gallery name')" />
            <x-text-input id="gallery_name" class="block mt-1 w-full" type="text" name="gallery_name"
                          :value="old('gallery_name')" required autofocus
                          placeholder="e.g. Slovenská národná galéria" />
            <x-input-error :messages="$errors->get('gallery_name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="city" :value="__('City')" />
                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="country" :value="__('Country')" />
                <x-text-input id="country" class="block mt-1 w-full" type="text" name="country" :value="old('country')" />
                <x-input-error :messages="$errors->get('country')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="website" :value="__('Website (optional)')" />
            <x-text-input id="website" class="block mt-1 w-full" type="url" name="website" :value="old('website')" placeholder="https://" />
            <x-input-error :messages="$errors->get('website')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>{{ __('Finish setup') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
