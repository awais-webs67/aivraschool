<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Settings">
    @foreach ([
        'admin.settings' => 'General',
        'admin.settings.registration' => 'Registration',
        'admin.settings.currencies' => 'Currencies',
        'admin.settings.email' => 'Email (SMTP)',
        'admin.settings.payments' => 'Payments (Stripe)',
        'admin.settings.security' => 'Security',
    ] as $route => $label)
        <a href="{{ route($route) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition {{ request()->routeIs($route) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
@error('env')<div class="alert alert-danger mb-6"><x-icon name="alert" class="mt-px" /><p>{{ $message }}</p></div>@enderror
