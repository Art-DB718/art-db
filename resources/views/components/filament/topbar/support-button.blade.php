{{-- Lifebuoy icon-button rendered in the Filament top bar, left of the
     user avatar. Links to the public help centre at /help; owners can
     always reach the direct email from inside that page too. --}}
<a href="{{ url('/help') }}"
   target="_blank"
   rel="noopener"
   title="{{ __('Help & support') }}"
   aria-label="{{ __('Help & support') }}"
   class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-md h-9 w-9 text-gray-500 hover:text-gray-700 focus-visible:ring-primary-600 dark:text-gray-400 dark:hover:text-gray-300">
    <x-filament::icon
        icon="heroicon-o-lifebuoy"
        class="h-5 w-5"
    />
</a>
