<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(string $role): View|RedirectResponse
    {
        $user = Auth::user();

        // Iba prihlásení a iba na vlastnú rolu.
        if ($user->role->value !== $role) {
            return redirect()->route('dashboard');
        }

        // Ak už onboarding prebehol, preskoč.
        if ($user->onboarded_at) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.'.$role, [
            'user' => $user,
        ]);
    }

    public function storeArtist(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isArtist(), 403);

        $data = $request->validate([
            'first_name'      => ['required', 'string', 'max:120'],
            'last_name'       => ['required', 'string', 'max:120'],
            'short_bio'       => ['nullable', 'string', 'max:2000'],
            'statement'       => ['nullable', 'string', 'max:4000'],
            'website'         => ['nullable', 'url', 'max:255'],
            'profile_image'   => ['nullable', 'image', 'max:5120'],
            'university_name' => ['nullable', 'string', 'max:255'],
            'field_of_study'  => ['nullable', 'string', 'max:120'],
            'degree_level'    => ['nullable', 'string', 'max:16'],
            'year_started'    => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'is_currently_studying' => ['nullable', 'boolean'],
        ]);

        $profileImage = null;
        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image')->store('artists', 'public');
        }

        // Resolve / create the University record from the typed name.
        $universityId = null;
        if (! empty($data['university_name'])) {
            $universityId = \App\Models\University::firstOrCreate(
                ['name' => trim($data['university_name'])],
            )->id;
        }

        Artist::firstOrCreate(
            ['owner_user_id' => $user->id],
            [
                'first_name'            => $data['first_name'],
                'last_name'             => $data['last_name'],
                'short_bio'             => $data['short_bio'] ?? null,
                'statement'             => $data['statement'] ?? null,
                'website'               => $data['website'] ?? null,
                'profile_image'         => $profileImage,
                'university_id'         => $universityId,
                'university_name'       => $data['university_name'] ?? null, // legacy backup field
                'field_of_study'        => $data['field_of_study'] ?? null,
                'degree_level'          => $data['degree_level'] ?? null,
                'year_started'          => $data['year_started'] ?? null,
                'is_currently_studying' => (bool) ($data['is_currently_studying'] ?? false),
                'is_published'          => false,
            ],
        );

        $user->forceFill(['onboarded_at' => now()])->save();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome — your artist profile has been created.');
    }

    public function storeGallery(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isGallery(), 403);

        $request->validate([
            'gallery_name' => ['required', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:120'],
            'country'      => ['nullable', 'string', 'max:120'],
            'website'      => ['nullable', 'url', 'max:255'],
        ]);

        // Institutional details stored on the user record (each gallery is its own institution).
        $user->forceFill([
            'institution_name'    => $request->gallery_name,
            'institution_city'    => $request->city,
            'institution_country' => $request->country,
            'institution_website' => $request->website,
            'onboarded_at'        => now(),
        ])->save();

        return redirect()->route('dashboard')
            ->with('status', 'Institution profile saved. You can now browse artworks and contact artists.');
    }

    public function storeUniversity(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isUniversity(), 403);

        $data = $request->validate([
            'university_name' => ['required', 'string', 'max:255'],
            'short_name'      => ['nullable', 'string', 'max:32'],
            'city'            => ['nullable', 'string', 'max:120'],
            'country'         => ['nullable', 'string', 'max:120'],
            'website'         => ['nullable', 'url', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:4000'],
        ]);

        // firstOrCreate by name — link to existing if a student already added this school.
        $university = \App\Models\University::firstOrCreate(
            ['name' => trim($data['university_name'])],
            [
                'short_name' => $data['short_name'] ?? null,
                'city'       => $data['city'] ?? null,
                'website'    => $data['website'] ?? null,
                'notes'      => $data['notes'] ?? null,
            ],
        );

        // If a row existed but our fields are richer, fill in any blanks.
        $university->fill(array_filter([
            'short_name' => $data['short_name'] ?? null,
            'city'       => $data['city'] ?? null,
            'website'    => $data['website'] ?? null,
            'notes'      => $data['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''))->save();

        $user->forceFill([
            'university_id' => $university->id,
            'onboarded_at'  => now(),
        ])->save();

        return redirect()->route('dashboard')
            ->with('status', 'University profile saved. Students at '.$university->name.' will appear here automatically.');
    }

    public function storeCollector(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isCollector(), 403);

        $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'name'          => $request->display_name,
            'onboarded_at'  => now(),
        ])->save();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome to '.config('app.name').'.');
    }
}
