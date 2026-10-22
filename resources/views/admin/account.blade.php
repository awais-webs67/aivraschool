<x-layouts.admin heading="My account" subheading="Your sign-in, two-factor security and devices.">
    @if ($codes = session('recovery_codes'))
        <section class="card mb-6 border-warning/40 bg-warning-tint/40 p-5" role="status">
            <p class="font-semibold text-ink">Save your recovery codes</p>
            <p class="mt-1 text-sm text-body">Each code signs you in once if you lose your phone. They are shown only now — keep them somewhere safe (not on the same phone).</p>
            <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                @foreach ($codes as $code)<code class="rounded border border-line bg-white px-3 py-2 text-center text-ink">{{ $code }}</code>@endforeach
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-4" data-copy="{{ implode("\n", $codes) }}"><x-icon name="copy" size="4" /> Copy all</button>
        </section>
    @endif

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Profile</h2></div>
            <dl class="card-body space-y-3 text-sm">
                <div><dt class="text-xs text-muted">Name</dt><dd class="text-ink">{{ $user->name }}</dd></div>
                <div><dt class="text-xs text-muted">Email</dt><dd class="break-words text-ink">{{ $user->email }}</dd></div>
                <div><dt class="text-xs text-muted">Role</dt><dd><span class="badge badge-brand">{{ $user->roleLabel() }}</span></dd></div>
                <div><dt class="text-xs text-muted">Last sign-in</dt><dd class="text-ink">{{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}{{ $user->last_login_ip ? ' · '.$user->last_login_ip : '' }}</dd></div>
            </dl>
        </section>

        <form method="POST" action="{{ route('admin.account.password') }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-header"><h2 class="card-title">Change password</h2></div>
            <div class="card-body space-y-4">
                <x-form.input name="current_password" id="pw-current" type="password" label="Current password" required autocomplete="current-password" />
                <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 10 characters with letters and numbers." />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Update password</button></div>
        </form>

        {{-- Two-factor sign-in --}}
        <section class="card lg:col-span-2">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Two-factor sign-in</h2>
                    <p class="mt-0.5 text-sm text-muted">After your password, also enter a code from an app on your phone (Google Authenticator, Microsoft Authenticator, Authy).</p>
                </div>
                <span class="badge {{ $user->hasTwoFactor() ? 'badge-success' : 'badge-warning' }}">{{ $user->hasTwoFactor() ? 'On' : 'Off' }}</span>
            </div>
            <div class="card-body">
                @if ($user->hasTwoFactor())
                    <div class="grid gap-4 sm:grid-cols-2">
                        <form method="POST" action="{{ route('admin.account.recovery-codes') }}" class="rounded-lg border border-line p-4">
                            @csrf
                            <p class="text-sm font-semibold text-ink">Recovery codes</p>
                            <p class="mt-1 text-xs text-muted">{{ count($user->two_factor_recovery_codes ?? []) }} unused. Make new ones if you lost them.</p>
                            <x-form.input name="current_password" id="rc-pass" type="password" label="Password" required class="mt-3" autocomplete="current-password" />
                            <button class="btn btn-secondary btn-sm mt-3">Make new recovery codes</button>
                        </form>
                        <form method="POST" action="{{ route('admin.account.two-factor.disable') }}" class="rounded-lg border border-danger/30 p-4" data-confirm="Turn off two-factor sign-in?">
                            @csrf
                            @method('DELETE')
                            <p class="text-sm font-semibold text-ink">Turn off</p>
                            <p class="mt-1 text-xs text-muted">Signing in will need only your password.</p>
                            <x-form.input name="current_password" id="off-pass" type="password" label="Password" required class="mt-3" autocomplete="current-password" />
                            <button class="btn btn-danger-outline btn-sm mt-3">Turn off two-factor</button>
                        </form>
                    </div>
                @elseif ($pendingSecret)
                    <div class="grid items-start gap-6 sm:grid-cols-[auto_1fr]">
                        <div class="mx-auto grid size-48 place-items-center rounded-lg border border-line bg-white p-2" data-qr="{{ \App\Support\Totp::uri($pendingSecret, $user->email) }}" aria-label="QR code for your authenticator app"></div>
                        <div class="space-y-4">
                            <ol class="list-decimal space-y-1 pl-5 text-sm text-body">
                                <li>Open your authenticator app and add an account.</li>
                                <li>Scan the QR code, or type this key: <code class="rounded bg-chip px-1.5 py-0.5 font-mono text-xs break-all text-ink">{{ trim(chunk_split($pendingSecret, 4, ' ')) }}</code></li>
                                <li>Enter the 6-digit code the app shows.</li>
                            </ol>
                            <form method="POST" action="{{ route('admin.account.two-factor.confirm') }}" class="flex flex-wrap items-end gap-3">
                                @csrf
                                <x-form.input name="code" label="Code from the app" required inputmode="numeric" autocomplete="one-time-code" class="w-44" />
                                <button class="btn btn-primary">Turn on</button>
                            </form>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.account.two-factor.enable') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <x-form.input name="current_password" id="on-pass" type="password" label="Confirm your password" required autocomplete="current-password" class="w-64" />
                        <button class="btn btn-primary"><x-icon name="shield" size="4" /> Set up two-factor</button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Devices --}}
        <section class="card">
            <div class="card-header"><h2 class="card-title">Signed-in devices</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($sessions as $s)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-chip text-steel"><x-icon name="globe" size="4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink">{{ \App\Support\UserAgent::describe($s->user_agent) }}</p>
                            <p class="truncate text-xs text-muted">{{ $s->ip_address }} · active {{ \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</p>
                        </div>
                        @if ($s->id === $currentSession)<span class="badge badge-success">This device</span>@endif
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-muted">Only this device.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('admin.account.logout-others') }}" class="flex flex-wrap items-end gap-3 border-t border-line px-5 py-4">
                @csrf
                <x-form.input name="current_password" id="dev-pass" type="password" label="Password" required autocomplete="current-password" class="min-w-0 flex-1" />
                <button class="btn btn-secondary">Sign out other devices</button>
            </form>
        </section>

        {{-- Sign-in history --}}
        <section class="card">
            <div class="card-header"><h2 class="card-title">Sign-in history</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($history as $log)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $log->action === 'login.failed' ? 'bg-danger-tint text-danger' : 'bg-success-tint text-success' }}"><x-icon :name="$log->action === 'login.failed' ? 'x' : 'check'" size="4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-ink">{{ $log->action === 'login.failed' ? 'Failed attempt' : 'Signed in' }} · {{ \App\Support\UserAgent::describe($log->user_agent) }}</p>
                            <p class="truncate text-xs text-muted">{{ $log->created_at->format('d M Y, h:i A') }} · {{ $log->ip_address ?? '—' }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-muted">No sign-ins recorded yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
