<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <p class="mt-1 text-xs text-gray-500">
                Artist accounts require a university email (e.g. <code>.edu</code>, <code>.ac.uk</code>, <code>vsvu.sk</code>, <code>vsmu.sk</code>).
            </p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Role picker -->
        <div class="mt-6">
            <x-input-label :value="__('I am a…')" />

            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ($roles as $role)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $role->value }}" class="peer sr-only"
                               {{ old('role', 'artist') === $role->value ? 'checked' : '' }} required>
                        <div class="border border-gray-300 rounded-md px-4 py-3 text-center transition
                                    peer-checked:border-gray-900 peer-checked:bg-gray-900 peer-checked:text-white
                                    hover:border-gray-700">
                            <span class="block font-semibold">{{ $role->label() }}</span>
                            <span class="block text-xs mt-1 opacity-80">
                                @switch($role->value)
                                    @case('artist') A student publishing their practice @break
                                    @case('gallery') An institution discovering students @break
                                    @case('collector') Collect from the start of a career @break
                                    @case('university') An art school presenting its students @break
                                @endswitch
                            </span>
                        </div>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
