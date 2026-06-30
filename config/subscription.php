<?php

/**
 * SaaS plan catalogue for Project Arch.
 *
 * Stripe Price IDs are env-driven so the same code works against test and
 * live keys without code changes. Trial length is global; per-plan limits
 * are enforced by the EnforceSubscriptionStatus middleware + future
 * model observers (artwork count, storage usage).
 */

return [
    'trial_days' => 14,

    // Days a user can stay in 'past_due' before status flips to 'archived'.
    // After that the admin is locked and a billing CTA is shown.
    'grace_days_after_trial' => 30,

    'plans' => [
        'trial' => [
            'label'       => 'Trial',
            'price_eur'   => 0,
            'roles'       => ['gallery', 'artist', 'collector'],
            'limits'      => ['galleries' => 1, 'artists' => 5, 'artworks' => 50, 'storage_gb' => 1],
            'description' => '14-day full access. No card required.',
        ],

        'collector_free' => [
            'label'       => 'Collector Free',
            'price_eur'   => 0,
            'roles'       => ['collector'],
            'limits'      => ['artworks' => 50, 'storage_gb' => 1],
            'description' => 'Private archive — up to 50 private artworks, 1 GB. Forever free.',
        ],

        'artist_free' => [
            'label'       => 'Free',
            'price_eur'   => 0,
            'roles'       => ['artist'],
            'limits'      => ['artworks' => 20, 'storage_gb' => 1],
            'description' => 'Publish up to 20 works on your public profile — forever free, no card.',
        ],

        'starter' => [
            'label'        => 'Starter',
            'price_eur'    => 9,
            'price_eur_yr' => 90, // 10 months (2 free)
            'stripe_price_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'stripe_price_yearly'  => env('STRIPE_PRICE_STARTER_YEARLY'),
            'roles'        => ['gallery', 'artist', 'collector'],
            'limits'       => ['galleries' => 1, 'artists' => 10, 'artworks' => 100, 'storage_gb' => 2],
            'description'  => 'For solo practice or a small gallery.',
        ],

        'pro' => [
            'label'        => 'Professional',
            'price_eur'    => 29,
            'price_eur_yr' => 290,
            'stripe_price_monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'stripe_price_yearly'  => env('STRIPE_PRICE_PRO_YEARLY'),
            'roles'        => ['gallery', 'artist', 'collector'],
            'limits'       => ['galleries' => 1, 'artists' => 50, 'artworks' => 500, 'storage_gb' => 10],
            'description'  => 'Growing gallery or serious collector.',
        ],

        'studio' => [
            'label'        => 'Studio',
            'price_eur'    => 79,
            'price_eur_yr' => 790,
            'stripe_price_monthly' => env('STRIPE_PRICE_STUDIO_MONTHLY'),
            'stripe_price_yearly'  => env('STRIPE_PRICE_STUDIO_YEARLY'),
            'roles'        => ['gallery', 'artist', 'collector'],
            'limits'       => ['galleries' => 3, 'artists' => null, 'artworks' => null, 'storage_gb' => 50],
            'description'  => 'Multi-gallery studio or institution.',
        ],

        'enterprise' => [
            'label'        => 'Enterprise',
            'price_eur'    => null,
            'roles'        => ['gallery', 'artist', 'collector'],
            'limits'       => ['galleries' => null, 'artists' => null, 'artworks' => null, 'storage_gb' => null],
            'description'  => 'Custom — multiple galleries, museum-scale storage, SLA.',
        ],
    ],
];
