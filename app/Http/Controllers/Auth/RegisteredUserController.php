<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniversityEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $publicRoles = array_map(fn ($r) => $r->value, UserRole::publicRegisterChoices());

        $emailRules = ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class];
        // Artist accounts require an academic email — applied conditionally
        // before validate() so the rule fires before the unique check.
        if ($request->input('role') === UserRole::Artist->value) {
            $emailRules[] = new UniversityEmail();
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => $emailRules,
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
