<?php

return [
    'inventory_prefix'  => env('ARTDB_INVENTORY_PREFIX', 'INV'),
    'inventory_padding' => (int) env('ARTDB_INVENTORY_PADDING', 4),
    'default_currency'  => env('ARTDB_DEFAULT_CURRENCY', 'EUR'),
    'default_locale'    => env('ARTDB_DEFAULT_LOCALE', 'sk'),

    /*
    |--------------------------------------------------------------------------
    | Artist signup — academic email only
    |--------------------------------------------------------------------------
    | When registering as Artist, the email must come from one of these
    | academic patterns. Edit to add a school we don't recognise yet.
    */
    'artist_email' => [
        // Always-allowed TLD patterns
        'patterns' => [
            '*.edu',       // US universities
            '*.edu.*',     // .edu.sk, .edu.au, etc.
            '*.ac.*',      // .ac.uk, .ac.at, .ac.in, etc.
            'uni-*.*',     // uni-leipzig.de, uni-wien.at, ...
        ],
        // Whitelisted full domains (Slovak art schools + selected EU)
        'allowed_domains' => [
            'vsvu.sk',      // Vysoká škola výtvarných umení v Bratislave
            'vsmu.sk',      // Vysoká škola múzických umení
            'uniba.sk',     // Univerzita Komenského
            'stuba.sk',     // STU Bratislava
            'tuke.sk',      // TU Košice
            'uniza.sk',     // Žilinská univerzita
            'umb.sk',       // UMB Banská Bystrica
            'upjs.sk',      // UPJŠ Košice
            'truni.sk',     // Trnavská univerzita
            'unipo.sk',     // Prešovská univerzita
            'ku.sk',        // Katolícka univerzita
            'fmk.ucm.sk',   // FMK UCM Trnava
            'akademiavu.cz',// AVU Praha (legacy)
            'avu.cz',       // AVU Praha
            'umprum.cz',    // VŠ UMPRUM
            'ffa.vutbr.cz', // FaVU VUT Brno
            'asp.krakow.pl',
            'asp.waw.pl',
            'asp.gda.pl',
        ],
    ],
];
