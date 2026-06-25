<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Onboarding</p>
        <h1 class="font-serif text-3xl">Tell us about your practice</h1>
        <p class="text-sm text-gray-500 mt-2">You can edit any of this later from your dashboard.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.artist') }}" enctype="multipart/form-data">
        @csrf

        {{-- IDENTITY --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="profile_image" :value="__('Profile image (optional)')" />
            <input id="profile_image" type="file" name="profile_image" accept="image/*"
                   class="block mt-1 w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:uppercase file:tracking-wider file:bg-gray-900 file:text-white hover:file:bg-gray-700">
            <x-input-error :messages="$errors->get('profile_image')" class="mt-2" />
        </div>

        {{-- ABOUT --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-4">About</p>

            <div>
                <x-input-label for="short_bio" :value="__('Short bio')" />
                <textarea id="short_bio" name="short_bio" rows="3" maxlength="2000"
                          placeholder="1-2 sentences. Where are you from, what do you make?"
                          class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('short_bio') }}</textarea>
                <x-input-error :messages="$errors->get('short_bio')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="statement" :value="__('Artist statement (optional)')" />
                <textarea id="statement" name="statement" rows="5" maxlength="4000"
                          placeholder="Your longer reflection on your practice — themes, materials, intentions."
                          class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('statement') }}</textarea>
                <x-input-error :messages="$errors->get('statement')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="website" :value="__('Website (optional)')" />
                <x-text-input id="website" class="block mt-1 w-full" type="url" name="website" :value="old('website')" placeholder="https://" />
                <x-input-error :messages="$errors->get('website')" class="mt-2" />
            </div>
        </div>

        {{-- UNIVERSITY --}}
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-4">University / studies</p>

            <div>
                <x-input-label for="university_name" :value="__('University')" />
                <input id="university_name" name="university_name" type="text" list="known-universities"
                       value="{{ old('university_name') }}"
                       placeholder="Start typing — pick from the list or add a new one"
                       class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <datalist id="known-universities">
                    @foreach (\App\Models\University::orderBy('name')->pluck('name') as $u)
                        <option value="{{ $u }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-gray-500">If your university isn't in the list, just type its full name — we'll add it.</p>
                <x-input-error :messages="$errors->get('university_name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="field_of_study" :value="__('Field of study')" />
                    <x-text-input id="field_of_study" class="block mt-1 w-full" type="text" name="field_of_study"
                                  :value="old('field_of_study')"
                                  placeholder="e.g. Painting, Sculpture, Photography" />
                    <x-input-error :messages="$errors->get('field_of_study')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="degree_level" :value="__('Degree')" />
                    <select id="degree_level" name="degree_level"
                            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">—</option>
                        @foreach (['BA' => 'BA (Bachelor)', 'MA' => 'MA (Master)', 'MFA' => 'MFA', 'PhD' => 'PhD', 'Diploma' => 'Diploma'] as $k => $label)
                            <option value="{{ $k }}" @selected(old('degree_level') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('degree_level')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="year_started" :value="__('Year started')" />
                    <x-text-input id="year_started" class="block mt-1 w-full" type="number" name="year_started"
                                  :value="old('year_started')" min="1900" max="{{ now()->year + 1 }}"
                                  placeholder="{{ now()->year }}" />
                    <x-input-error :messages="$errors->get('year_started')" class="mt-2" />
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_currently_studying" value="0">
                        <input id="is_currently_studying" type="checkbox" name="is_currently_studying" value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               @checked(old('is_currently_studying'))>
                        <span class="ml-2 text-sm text-gray-700">Currently studying</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8">
            <x-primary-button>{{ __('Finish setup') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
