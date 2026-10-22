<?php

namespace App\Http\Controllers\School\People;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Gives a parent a portal login for a student (and their brothers and sisters).
 * A parent who already has a login with the same phone is simply linked.
 */
class ParentLoginController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        $schoolId = current_school()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'password' => ['nullable', Password::min(6)],
        ]);
        $phone = User::normalizePhone($data['phone']);
        if (! $phone) {
            return back()->withInput()->withErrors(['phone' => 'Enter a full phone number.']);
        }

        $parent = User::query()->where('phone', $phone)->first();
        if ($parent && ! $parent->isParent()) {
            return back()->withInput()->withErrors(['phone' => 'A staff login already uses this phone number.']);
        }
        if (! $parent && blank($data['password'] ?? null)) {
            return back()->withInput()->withErrors(['password' => 'Choose a password for the new login.']);
        }
        if (filled($data['email'] ?? null) && User::query()->where('email', Str::lower($data['email']))->when($parent, fn ($q) => $q->whereKeyNot($parent->id))->exists()) {
            return back()->withInput()->withErrors(['email' => 'Another login already uses this email.']);
        }

        $fields = ['name' => $data['name'], 'phone' => $phone, 'email' => filled($data['email'] ?? null) ? Str::lower($data['email']) : $parent?->email, 'role' => 'parent', 'is_active' => true];
        if (filled($data['password'] ?? null)) {
            $fields['password'] = $data['password'];
        }

        $parent ? $parent->update($fields) : $parent = User::create($fields);

        // Link this student and every brother / sister.
        $ids = $student->siblings()->where('status', 'active')->pluck('id')->push($student->id);
        $parent->children()->syncWithoutDetaching($ids->all());
        Activity::log('student.parent_login', "Parent login for {$student->name} ({$parent->name})", $student);

        return back()->with('success', "{$parent->name} can now sign in with phone {$data['phone']}".(filled($data['password'] ?? null) ? ' and the password you set.' : '.'));
    }

    public function destroy(Student $student, User $parent): RedirectResponse
    {
        abort_unless($parent->isParent(), 404);
        $parent->children()->detach($student->id);

        if (! $parent->children()->exists()) {
            $parent->update(['is_active' => false]);
        }

        return back()->with('success', "{$parent->name} can no longer see {$student->name}.");
    }
}
