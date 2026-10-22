<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * AivraSol staff who can sign in to the Super Admin panel, each with a role.
 */
class TeamController extends Controller
{
    public function index(): View
    {
        return view('admin.team.index', [
            'members' => PlatformUser::query()->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:platform_users,email'],
            'role' => ['required', Rule::in(array_keys(PlatformUser::ROLES))],
            'password' => ['nullable', 'string', 'min:10', 'max:100'],
        ]);

        $password = ($data['password'] ?? null) ?: Str::password(12, symbols: false);

        $member = PlatformUser::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'],
            'password' => $password,
        ]);

        Activity::log('team.added', "Added {$member->name} as {$member->roleLabel()}", $member);

        return back()->with('success', "{$member->name} can now sign in.")
            ->with('credentials', ['url' => route('admin.login'), 'email' => $member->email, 'password' => $password]);
    }

    public function edit(PlatformUser $member): View
    {
        return view('admin.team.edit', ['member' => $member]);
    }

    public function update(Request $request, PlatformUser $member): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('platform_users', 'email')->ignore($member->id)],
            'role' => ['required', Rule::in(array_keys(PlatformUser::ROLES))],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['email'] = strtolower($data['email']);

        $losingOwner = $member->isSuperAdmin() && $member->is_active && ($data['role'] !== 'super_admin' || ! $data['is_active']);

        if ($member->is($request->user()) && ($losingOwner || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => 'You can\'t remove your own Super Admin access or deactivate yourself.']);
        }

        if ($losingOwner && PlatformUser::query()->where('role', 'super_admin')->where('is_active', true)->count() === 1) {
            throw ValidationException::withMessages(['role' => 'There must always be at least one active Super Admin.']);
        }

        $member->update($data);
        Activity::log('team.updated', "Updated {$member->name} ({$member->roleLabel()}".($member->is_active ? '' : ', deactivated').')', $member);

        return redirect()->route('admin.team.index')->with('success', "{$member->name} updated.");
    }

    public function resetPassword(PlatformUser $member): RedirectResponse
    {
        $password = Str::password(12, symbols: false);
        $member->update(['password' => $password]);

        Activity::log('team.password', "Reset password for {$member->name}", $member);

        return back()->with('success', 'A new password was generated.')
            ->with('credentials', ['url' => route('admin.login'), 'email' => $member->email, 'password' => $password]);
    }
}
