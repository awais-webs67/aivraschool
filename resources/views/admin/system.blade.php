<x-layouts.admin heading="System" subheading="Server status and database updates.">
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Server</h2></div>
            <dl class="card-body space-y-3 text-sm">
                @foreach ($checks as $label => $value)
                    <div class="grid grid-cols-[10rem_1fr] gap-3">
                        <dt class="text-muted">{{ $label }}</dt>
                        <dd class="break-words text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Database updates</h2>
                @if (count($pending))
                    <span class="badge badge-warning">{{ count($pending) }} pending</span>
                @else
                    <span class="badge badge-success">Up to date</span>
                @endif
            </div>
            <div class="card-body space-y-4 text-sm">
                <p class="text-body">After uploading a new version of Skooline, run the updates here. Existing data is kept.</p>
                @if (count($pending))
                    <ul class="space-y-1 rounded border border-line bg-canvas p-3 font-mono text-xs text-ink">
                        @foreach ($pending as $migration)<li class="break-all">{{ $migration }}</li>@endforeach
                    </ul>
                    <form method="POST" action="{{ route('admin.system.migrate') }}" data-confirm="Run database updates now? Take a database backup first.">
                        @csrf
                        <button class="btn btn-primary"><x-icon name="refresh" size="4" /> Run updates</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Backup</h2>
                    <p class="mt-0.5 text-sm text-muted">Download a copy before updates and keep it somewhere safe. It holds every school's data.</p>
                </div>
            </div>
            <div class="card-body flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.system.backup') }}" data-no-progress>
                    @csrf
                    <button class="btn btn-primary"><x-icon name="download" size="4" /> Database (.sql.gz)</button>
                </form>
                @if ($canZip)
                    <form method="POST" action="{{ route('admin.system.files') }}" data-no-progress>
                        @csrf
                        <button class="btn btn-secondary"><x-icon name="download" size="4" /> Uploaded files (.zip)</button>
                    </form>
                @endif
            </div>
            <p class="border-t border-line px-5 py-3 text-xs text-muted">To restore: create an empty database, then phpMyAdmin → Import the .sql.gz file.</p>
        </section>

        <form method="POST" action="{{ route('admin.system.maintenance') }}" class="card">
            @csrf
            <div class="card-header">
                <div>
                    <h2 class="card-title">Maintenance mode</h2>
                    <p class="mt-0.5 text-sm text-muted">Shows a "back soon" page on the website and all school portals. This panel keeps working.</p>
                </div>
                <span class="badge {{ ! empty($maintenance['on']) ? 'badge-warning' : 'badge-success' }}">{{ ! empty($maintenance['on']) ? 'On' : 'Off' }}</span>
            </div>
            <div class="card-body space-y-4">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="on" value="0">
                    <input type="checkbox" name="on" value="1" class="checkbox mt-0.5" @checked(! empty($maintenance['on']))>
                    <span class="text-sm font-semibold text-ink">Turn on maintenance mode</span>
                </label>
                <x-form.textarea name="message" label="Message for visitors (optional)" rows="2" :value="$maintenance['message'] ?? ''" maxlength="500" />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save</button></div>
        </form>

        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Cron job (required)</h2></div>
            <div class="card-body space-y-3 text-sm">
                <p class="text-body">Add this once in cPanel → Cron Jobs or hPanel → Advanced → Cron Jobs, set to run <strong>every minute</strong>. It expires overdue subscriptions, sends reminder emails and queued messages.</p>
                <div class="flex items-center gap-2 rounded border border-line bg-canvas p-3">
                    <code class="min-w-0 flex-1 break-all text-xs text-ink">{{ $cron }}</code>
                    <button type="button" class="btn btn-secondary btn-sm shrink-0" data-copy="{{ $cron }}"><x-icon name="copy" size="4" /> Copy</button>
                </div>
            </div>
        </section>
    </div>
</x-layouts.admin>
