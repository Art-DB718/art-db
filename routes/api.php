<?php

use App\Http\Controllers\Api\V1\ArtistController;
use App\Http\Controllers\Api\V1\ArtworkController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\ExhibitionController;
use App\Http\Controllers\Api\V1\GalleryController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ===== AUTH =====
    Route::post('/tokens', [TokenController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user',              [TokenController::class, 'me']);
        Route::delete('/tokens/current', [TokenController::class, 'destroy']);

        // Admin-only full export (gated inside controller via Gate::allows).
        Route::get('/export/full', [\App\Http\Controllers\Api\V1\ExportController::class, 'full'])
            ->name('api.export.full');
    });

    // ===== PUBLIC READ-ONLY =====
    // Public artwork / artist / collection / exhibition data — no auth needed.
    Route::get('/artworks',                [ArtworkController::class, 'index']);
    Route::get('/artworks/{artwork:slug}', [ArtworkController::class, 'show']);

    Route::get('/artists',                       [ArtistController::class, 'index']);
    Route::get('/artists/{artist:slug}',         [ArtistController::class, 'show']);
    Route::get('/artists/{artist:slug}/artworks',[ArtistController::class, 'artworks']);

    Route::get('/collections',                       [CollectionController::class, 'index']);
    Route::get('/collections/{collection:slug}',     [CollectionController::class, 'show']);

    Route::get('/exhibitions',                       [ExhibitionController::class, 'index']);
    Route::get('/exhibitions/{exhibition:slug}',     [ExhibitionController::class, 'show']);

    Route::get('/galleries',                         [GalleryController::class, 'index']);
    Route::get('/galleries/{gallery:slug}',          [GalleryController::class, 'show']);
    Route::get('/galleries/{gallery:slug}/artists',  [GalleryController::class, 'artists']);
});
