<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'roles' => UserRole::publicRegisterChoices(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot — the hidden `website` field must come back empty.
        // Any bot filling the form blindly trips it; silent 302 back.
        if (filled($request->input('website'))) {
            return redirect(route('register'));
        }

        // Rate-limit per IP — 3 attempts per hour. Keeps a single
        // spammer / mass-registration bot from carpet-bombing the
        // database with throwaway accounts.
        $throttleKey = 'register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Please try again in an hour.',
            ]);
        }
        RateLimiter::hit($throttleKey, 3600);

        $publicRoles = array_map(fn ($r) => $r->value, UserRole::publicRegisterChoices());

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', Rule::in($publicRoles)],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Po registrácii → onboarding wizard špecifický pre rolu.
        return redirect(route('onboarding.show', ['role' => $user->role->value], absolute: false));
    }
}
