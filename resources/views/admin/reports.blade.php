<x-layouts.admin heading="Reports" subheading="Revenue, growth and outstanding payments across Skooline.">
    <x-slot:actions>
        <div class="flex rounded-md border border-line-strong bg-white p-0.5 text-sm">
            @foreach ([6, 12, 24] as $m)
                <a href="{{ route('admin.reports', ['months' => $m]) }}" class="rounded px-3 py-1.5 font-medium transition {{ $months === $m ? 'bg-ink text-white' : 'text-body hover:text-ink' }}">{{ $m }} months</a>
            @endforeach
        </div>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Collected ({{ $months }} months)" :value="money($totals['collected'])" icon="banknotes" tone="success" />
        <x-stat label="Invoiced ({{ $months }} months)" :value="money($totals['invoiced'])" icon="credit-card" tone="neutral" />
        <x-stat label="New schools ({{ $months }} months)" :value="number_format($totals['schools'])" icon="building" tone="info" />
        <x-stat label="Collected all time" :value="money($totals['lifetime'])" icon="trending" tone="brand" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Revenue collected</h2></div>
            <div class="card-body pt-10"><x-bar-chart :series="$collected" money /></div>
        </section>
        <section class="card">
            <div class="card-header"><h2 class="card-title">New schools</h2></div>
            <div class="card-body pt-10"><x-bar-chart :series="$newSchools" tone="info" /></div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Month by month</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Month</th><th class="text-right">Invoiced</th><th class="text-right">Collected</th><th class="text-right">New schools</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="font-medium text-ink">{{ $row['label'] }}</td>
                                <td class="tabular text-right">{{ money($row['invoiced']) }}</td>
                                <td class="tabular text-right font-medium text-ink">{{ money($row['collected']) }}</td>
                                <td class="tabular text-right">{{ $row['schools'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">By plan</h2></div>
                <ul class="divide-y divide-line">
                    @foreach ($byPlan as $row)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-semibold text-ink">{{ $row['plan']->name }}</p>
                                <span class="badge badge-neutral">{{ $row['schools'] }} {{ \Illuminate\Support\Str::plural('school', $row['schools']) }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-muted">Collected {{ money($row['revenue']) }} · Outstanding {{ money($row['outstanding']) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Exports</h2></div>
                <div class="card-body grid gap-2">
                    @can('schools.view')<a href="{{ route('admin.schools.export') }}" class="btn btn-secondary justify-start"><x-icon name="download" size="4" /> Schools (CSV)</a>@endcan
                    @can('billing')<a href="{{ route('admin.subscriptions.export') }}" class="btn btn-secondary justify-start"><x-icon name="download" size="4" /> Invoices (CSV)</a>@endcan
                    @can('leads')<a href="{{ route('admin.demo-requests.export') }}" class="btn btn-secondary justify-start"><x-icon name="download" size="4" /> Leads (CSV)</a>@endcan
                </div>
            </section>
        </div>
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Oldest unpaid invoices</h2>
            @can('billing')<a href="{{ route('admin.subscriptions.index', ['payment_status' => 'unpaid']) }}" class="text-sm font-medium text-brand-text hover:underline">All unpaid</a>@endcan
        </div>
        @if ($outstanding->isEmpty())
            <p class="card-body text-sm text-muted">Every invoice is paid. 🎉</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Invoice</th><th>School</th><th>Period start</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @foreach ($outstanding as $s)
                            <tr>
                                <td class="font-mono text-xs font-semibold text-ink">{{ $s->invoice_number }}</td>
                                <td>@if ($s->school)<a href="{{ route('admin.schools.show', $s->school) }}" class="font-medium text-ink hover:text-brand-text">{{ $s->school->name }}</a>@else — @endif</td>
                                <td class="tabular">{{ $s->starts_at->format('d M Y') }} <span class="text-xs text-muted">({{ $s->starts_at->diffForHumans() }})</span></td>
                                <td class="tabular text-right font-medium text-danger">{{ money($s->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
