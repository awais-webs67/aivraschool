<x-layouts.admin heading="Team" subheading="People at AivraSol who can sign in to this panel, and what each one may do.">
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Members</h2>
                <span class="text-sm text-muted">{{ $members->where('is_active', true)->count() }} active</span>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($members as $member)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-4 {{ $member->is_active ? '' : 'opacity-60' }}">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-steel-2 to-ink font-semibold text-white">{{ strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                        <div class="min-w-[12rem] flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                                <span class="truncate">{{ $member->name }}</span>
                                @if ($member->is(auth()->user()))<span class="badge badge-neutral">You</span>@endif
                                @unless ($member->is_active)<span class="badge badge-danger">Inactive</span>@endunless
                            </p>
                            <p class="truncate text-sm text-muted">{{ $member->email }}</p>
                            <p class="text-xs text-muted">{{ $member->last_login_at ? 'Last sign-in '.$member->last_login_at->diffForHumans().($member->last_login_ip ? ' from '.$member->last_login_ip : '') : 'Never signed in' }}</p>
                        </div>
                        <div class="ml-auto flex items-center gap-1">
                            <span class="badge mr-1 {{ $member->isSuperAdmin() ? 'badge-brand' : 'badge-info' }}">{{ $member->roleLabel() }}</span>
                            <a href="{{ route('admin.team.edit', $member) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" size="4" /><span class="sr-only">Edit {{ $member->name }}</span></a>
                            <form method="POST" action="{{ route('admin.team.reset-password', $member) }}" data-confirm="Generate a new password for {{ $member->email }}?">
                                @csrf
                                <button class="btn btn-ghost btn-sm" title="Reset password"><x-icon name="key" size="4" /><span class="sr-only">Reset password</span></button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.team.store') }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">Add member</h2></div>
                <div class="card-body space-y-4">
                    <x-form.input name="name" label="Full name" required />
                    <x-form.input name="email" type="email" label="Email (username)" required autocomplete="off" />
                    <x-form.select name="role" label="Role" required value="support" :options="\App\Models\PlatformUser::ROLES" />
                    <x-form.input name="password" type="password" label="Password" autocomplete="new-password" hint="Leave blank to generate one." />
                    <button class="btn btn-primary w-full"><x-icon name="plus" size="4" /> Add member</button>
                </div>
            </form>

            <section class="card">
                <div class="card-header"><h2 class="card-title">What each role can do</h2></div>
                <div class="card-body space-y-4 text-sm">
                    <div>
                        <p class="font-semibold text-ink">Owner (Super Admin)</p>
                        <p class="text-muted">Everything, including team, settings and system updates.</p>
                    </div>
                    @foreach (\App\Models\PlatformUser::PERMISSIONS as $role => $permissions)
                        <div>
                            <p class="font-semibold text-ink">{{ \App\Models\PlatformUser::ROLES[$role] }}</p>
                            <p class="text-muted">{{ collect($permissions)->map(fn ($p) => \App\Models\PlatformUser::PERMISSION_LABELS[$p])->join(', ') }}.</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
