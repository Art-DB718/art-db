<?php

/**
 * Printable PDFs — shared config.
 *
 * `languages` is the catalogue of locales the Print menu offers and the
 * only list PrintController accepts via ?lang=xx. Each key is the
 * Laravel locale code (translation files live in lang/{key}/...),
 * each value is the display name shown on the picker in its own
 * language. Keep this list in sync with the translation files on disk.
 */

return [
    'languages' => [
        'en' => 'English',
        'sk' => 'Slovenčina',
        'de' => 'Deutsch',
        'cs' => 'Čeština',
        'hu' => 'Magyar',
        'pl' => 'Polski',
        'uk' => 'Українська',
        'sr' => 'Srpski',
        'hr' => 'Hrvatski',
        'fr' => 'Français',
        'nl' => 'Nederlands',
    ],

    'default_language' => 'en',
];
