<?php

// Broad-strokes smoke tests: every public route returns 2xx / 3xx even on
// an empty DB. Catches gross regressions like missing views, broken routes,
// or newly-added HomeController queries that assume seeded data.

it('serves the home page', function () {
    $this->get('/')->assertStatus(200);
});

it('serves the /up health endpoint', function () {
    $this->get('/up')->assertStatus(200);
});

it('serves the sitemap', function () {
    $this->get('/sitemap.xml')->assertOk();
    // robots.txt is a static file in public/ — served by the web server
    // in prod, not by Laravel's HTTP test kernel, so we don't assert here.
});

it('serves the public archive listings', function () {
    $this->get('/artworks')->assertOk();
    $this->get('/artists')->assertOk();
    $this->get('/galleries')->assertOk();
    $this->get('/exhibitions')->assertOk();
    $this->get('/collections')->assertOk();
});

it('serves the platform pages', function () {
    $this->get('/platform')->assertOk();
    $this->get('/platform/gallery')->assertOk();
    $this->get('/platform/artist')->assertOk();
    $this->get('/platform/collector')->assertOk();
});

it('legacy /login redirects to Filament panel', function () {
    $this->get('/login')->assertRedirect('/admin/login');
    $this->get('/register')->assertRedirect('/admin/register');
    $this->get('/forgot-password')->assertRedirect('/admin/password/reset');
});
