@php
    $user = filament()->auth()->user();
@endphp

{{--
    Override of Filament's default AccountWidget sign-out form.

    The stock widget wraps the submit button in <x-filament::button>, which
    picks up Alpine.js and Livewire lifecycle attributes. In this app that
    caused the POST /admin/logout to race with a wire:navigate-style
    intercept, so on click the user was logged out server-side but then
    bounced back to /admin (still-authenticated cached page) and finally
    /admin/billing via the subscription middleware. Rendering a plain
    HTML <button> keeps the form as a bog-standard synchronous POST →
    302 → /admin/login flow, which is what everyone expects from a sign
    out control.
--}}

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <x-filament-panels::avatar.user size="lg" :user="$user" />

            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ filament()->getUserName($user) }}
                </p>
            </div>

            {{--
                Plain anchor to the dedicated GET force-logout route.
                Filament's own POST /admin/logout works from a fetch call,
                but wiring it up as a real form submit inside this Livewire
                widget consistently drops the user on /admin/billing due to
                the sign-out response racing a cached Livewire page. A GET
                link sidesteps every one of those races.
            --}}
            <a
                href="{{ route('admin.force-logout') }}"
                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                data-no-turbolink
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-btn-icon transition duration-75 h-5 w-5 -ms-1 text-gray-400 dark:text-gray-500">
                    <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M6 10a.75.75 0 0 1 .75-.75h9.546l-1.048-.943a.75.75 0 1 1 1.004-1.114l2.5 2.25a.75.75 0 0 1 0 1.114l-2.5 2.25a.75.75 0 1 1-1.004-1.114l1.048-.943H6.75A.75.75 0 0 1 6 10Z" clip-rule="evenodd" />
                </svg>
                <span>{{ __('filament-panels::widgets/account-widget.actions.logout.label') }}</span>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
