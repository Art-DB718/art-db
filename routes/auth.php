<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth route stubs
|--------------------------------------------------------------------------
| Authentication for this app lives entirely inside the Filament admin
| panel (/admin/login, /admin/register, /admin/password/reset). The
| legacy Breeze scaffolding that came with the Art DB port (with the
| broken <x-guest-layout> component) is no longer wired up.
|
| These stub routes only exist so the named-route helpers used across
| the public templates — route('login'), route('register'), route('logout'),
| route('dashboard'), route('password.request') — keep resolving instead
| of throwing RouteNotFoundException. Each one redirects the visitor to
| the equivalent Filament page.
*/

Route::get('/login',  fn () => redirect('/admin/login'))->name('login');
Route::get('/register', fn () => redirect('/admin/register'))->name('register');

Route::get('/forgot-password', fn () => redirect('/admin/password/reset'))
    ->name('password.request');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => redirect('/admin'))->name('dashboard');

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
