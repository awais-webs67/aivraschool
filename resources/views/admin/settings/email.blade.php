<x-layouts.admin heading="Settings" subheading="Send registration, approval and login emails through your own mail server.">
    @include('admin.settings.tabs')

    <div class="grid max-w-5xl gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.settings.email.update') }}" class="card xl:col-span-2">
            @csrf
            @method('PUT')
            <div class="card-header">
                <div>
                    <h2 class="card-title">SMTP server</h2>
                    <p class="mt-0.5 text-sm text-muted">Saved to the server's <code>.env</code> file. The password is never shown without your PIN.</p>
                </div>
                <span class="badge {{ $configured ? 'badge-success' : 'badge-neutral' }}">{{ $configured ? 'Set up' : 'Not set up' }}</span>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="MAIL_HOST" label="SMTP host" :value="$env['MAIL_HOST'] === '127.0.0.1' ? null : $env['MAIL_HOST']" required placeholder="smtp.gmail.com or mail.yourdomain.com" />
                <div class="grid grid-cols-2 gap-3">
                    <x-form.input name="MAIL_PORT" type="number" label="Port" :value="$env['MAIL_PORT'] === '2525' ? 587 : $env['MAIL_PORT']" required />
                    <x-form.select name="MAIL_ENCRYPTION" label="Encryption" :value="$env['MAIL_ENCRYPTION'] ?? 'tls'" :options="['tls' => 'TLS (587)', 'ssl' => 'SSL (465)']" />
                </div>
                <x-form.input name="MAIL_USERNAME" label="Username" :value="$env['MAIL_USERNAME']" autocomplete="off" />
                <x-secret-input name="MAIL_PASSWORD" label="Password" :value="$env['MAIL_PASSWORD']" hint="Gmail: use an App Password." />
                <x-form.input name="MAIL_FROM_ADDRESS" type="email" label="From email" :value="$env['MAIL_FROM_ADDRESS'] === 'hello@example.com' ? null : $env['MAIL_FROM_ADDRESS']" required />
                <x-form.input name="MAIL_FROM_NAME" label="From name" :value="$env['MAIL_FROM_NAME'] ?: 'Skooline'" required />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save email settings</button></div>
        </form>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.settings.email.test') }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">Send a test email</h2></div>
                <div class="card-body space-y-4">
                    <x-form.input name="to" type="email" label="Send to" :value="auth()->user()->email" required />
                    <button class="btn btn-secondary w-full" @disabled(! $configured)><x-icon name="send" size="4" /> Send test</button>
                    @if (session('error_detail'))<p class="rounded bg-canvas p-2 font-mono text-[11px] break-words text-muted">{{ session('error_detail') }}</p>@endif
                </div>
            </form>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Common settings</h2></div>
                <dl class="card-body space-y-2 text-sm">
                    @foreach (['Gmail' => 'smtp.gmail.com · 587 · TLS · App Password', 'Outlook / Microsoft 365' => 'smtp.office365.com · 587 · TLS', 'cPanel / hPanel mail' => 'mail.yourdomain.com · 465 · SSL', 'Zoho' => 'smtp.zoho.com · 465 · SSL'] as $provider => $values)
                        <div><dt class="font-semibold text-ink">{{ $provider }}</dt><dd class="text-muted">{{ $values }}</dd></div>
                    @endforeach
                </dl>
            </section>
        </div>
    </div>
</x-layouts.admin>
