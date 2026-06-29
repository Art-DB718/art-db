<?php

use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyArtworksController;
use App\Http\Controllers\MyCollectionController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PrivateRoomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Verejný web (Fáza 2) — domovská stránka + stub routes pre ostatné sekcie.
Route::get('/', HomeController::class)->name('home');

Route::get('/artworks', [ArtworkController::class, 'index'])->name('artworks.index');
Route::get('/artworks/{artwork:slug}', [ArtworkController::class, 'show'])->name('artworks.show');
Route::post('/artworks/{artwork:slug}/inquire', [ArtworkController::class, 'inquire'])->name('artworks.inquire');

Route::get('/artists', [ArtistController::class, 'index'])->name('artists.index');
Route::get('/artists/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
Route::post('/artists/{artist:slug}/contact', [ArtistController::class, 'contact'])
    ->middleware('auth')->name('artists.contact');

Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])->name('collections.show');

Route::get('/exhibitions', [ExhibitionController::class, 'index'])->name('exhibitions.index');
Route::get('/exhibitions/{exhibition:slug}', [ExhibitionController::class, 'show'])->name('exhibitions.show');

Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
Route::get('/galleries/{gallery:slug}', [GalleryController::class, 'show'])->name('galleries.show');

Route::get('/platform',           [PlatformController::class, 'index'])->name('platform');
Route::get('/platform/gallery',   [PlatformController::class, 'gallery'])->name('platform.gallery');
Route::get('/platform/artist',    [PlatformController::class, 'artist'])->name('platform.artist');
Route::get('/platform/collector', [PlatformController::class, 'collector'])->name('platform.collector');
// Backward-compat: old /about URL redirects to /platform.
Route::redirect('/about', '/platform', 301);

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Private Room — token-based, prístupný komukoľvek s linkom (žiadny auth).
Route::get('/private-room/{token}', [PrivateRoomController::class, 'show'])->name('private-room.show');
Route::post('/private-room/{token}/inquire', [PrivateRoomController::class, 'inquire'])->name('private-room.inquire');

// Stripe Checkout (public — visitor pays directly).
Route::post('/artworks/{artwork:slug}/buy',      [CheckoutController::class, 'buy'])->name('checkout.buy');
Route::get('/checkout/success/{artwork:slug}',   [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/stripe/webhook',                   [CheckoutController::class, 'webhook'])->name('stripe.webhook');

Route::post('/newsletter/subscribe', NewsletterController::class)
    ->name('newsletter.subscribe');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // "My X" zóny pre Artist/Collector (Gallery sa rediretuje na /admin).
    Route::get('/my/artworks',   MyArtworksController::class)->name('my.artworks');
    Route::get('/my/collection', [MyCollectionController::class, 'index'])->name('my.collection');

    // Like / Save toggles — Gallery + Collector only (Artist not allowed).
    Route::post('/artworks/{artwork:slug}/like', [MyCollectionController::class, 'toggleLike'])->name('artworks.like');
    Route::post('/artworks/{artwork:slug}/save', [MyCollectionController::class, 'toggleSave'])->name('artworks.save');

    // Tlačiteľný HTML náhľad faktúry — otvára sa v novom okne, auto-print.
    Route::get('/prints/sale/{sale}', function (\App\Models\Sale $sale) {
        $sale->load(['buyer', 'lineItems.artwork.artist']);

        return view('prints.invoice', [
            'sale'     => $sale,
            'settings' => \App\Models\InvoiceSetting::current(),
        ]);
    })->name('sales.print');

    // Tlačiteľné pohľady pre Artwork: karta diela, certifikát, štítok.
    Route::get('/prints/artwork/{artwork}/card', function (\App\Models\Artwork $artwork) {
        return view('prints.artwork-card', [
            'artwork'  => $artwork->load(['artist', 'medium', 'genre']),
            'settings' => \App\Models\InvoiceSetting::current(),
        ]);
    })->name('artworks.print.card');

    Route::get('/prints/artwork/{artwork}/certificate', function (\App\Models\Artwork $artwork) {
        return view('prints.artwork-certificate', [
            'artwork'  => $artwork->load(['artist', 'medium']),
            'settings' => \App\Models\InvoiceSetting::current(),
        ]);
    })->name('artworks.print.certificate');

    Route::get('/prints/artwork/{artwork}/label', function (\App\Models\Artwork $artwork) {
        return view('prints.artwork-label', [
            'artwork'  => $artwork->load(['artist', 'medium']),
            'settings' => \App\Models\InvoiceSetting::current(),
        ]);
    })->name('artworks.print.label');

    Route::get('/prints/artwork/{artwork}/maintenance', function (\App\Models\Artwork $artwork) {
        return view('prints.artwork-maintenance', [
            'artwork'  => $artwork->load(['artist', 'medium', 'maintenances']),
            'settings' => \App\Models\InvoiceSetting::current(),
        ]);
    })->name('artworks.print.maintenance');

    // PDF varianty (download cez dompdf).
    Route::get('/prints/sale/{sale}.pdf',                  [PrintController::class, 'saleInvoice'])->name('sales.pdf');
    Route::get('/prints/artwork/{artwork}/card.pdf',       [PrintController::class, 'artworkCard'])->name('artworks.pdf.card');
    Route::get('/prints/artwork/{artwork}/certificate.pdf',[PrintController::class, 'artworkCertificate'])->name('artworks.pdf.certificate');
    Route::get('/prints/artwork/{artwork}/label.pdf',      [PrintController::class, 'artworkLabel'])->name('artworks.pdf.label');
    Route::get('/prints/artwork/{artwork}/maintenance.pdf',[PrintController::class, 'artworkMaintenance'])->name('artworks.pdf.maintenance');
});

require __DIR__.'/auth.php';
