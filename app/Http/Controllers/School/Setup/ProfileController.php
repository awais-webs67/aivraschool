<?php

namespace App\Http\Controllers\School\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('school.setup.profile', ['school' => current_school()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = current_school();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'principal_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', Rule::in(array_keys(config('currencies')))],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_logo' => ['boolean'],
        ]);

        if ($request->boolean('remove_logo') && $school->logo_path) {
            Storage::delete($school->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::delete($school->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->storeAs(
                "schools/{$school->id}",
                'logo-'.now()->timestamp.'.'.$request->file('logo')->extension(),
            );
        }

        unset($data['logo'], $data['remove_logo']);
        $school->update($data);

        return back()->with('success', 'School profile saved.');
    }

    /** The logo is stored privately (no storage symlink needed on shared hosting). */
    public function logo(): Response
    {
        $path = current_school()->logo_path;

        abort_unless($path && Storage::exists($path), 404);

        return Storage::response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    }
}
