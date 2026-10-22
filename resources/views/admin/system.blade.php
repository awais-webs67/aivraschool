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

        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Cron job (required)</h2></div>
            <div class="card-body space-y-3 text-sm">
                <p class="text-body">Add this once in cPanel → Cron Jobs or hPanel → Advanced → Cron Jobs, set to run <strong>every minute</strong>. It expires overdue subscriptions and sends queued messages.</p>
                <div class="flex items-center gap-2 rounded border border-line bg-canvas p-3">
                    <code class="min-w-0 flex-1 break-all text-xs text-ink">{{ $cron }}</code>
                    <button type="button" class="btn btn-secondary btn-sm shrink-0" data-copy="{{ $cron }}"><x-icon name="copy" size="4" /> Copy</button>
                </div>
            </div>
        </section>
    </div>
</x-layouts.admin>
