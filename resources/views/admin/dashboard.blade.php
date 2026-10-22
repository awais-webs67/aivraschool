<x-layouts.admin heading="Dashboard" :subheading="'Welcome back, '.auth()->user()->name.'. Here is how Skooline is doing today.'">
    @php
        $user = auth()->user();
        $change = $collectedLastMonth > 0 ? round(($collectedThisMonth - $collectedLastMonth) / $collectedLastMonth * 100) : null;
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total schools" :value="number_format($total)" icon="building" tone="neutral"
            :hint="($counts['active'] ?? 0).' active · '.($counts['trial'] ?? 0).' on trial'" />
        <x-stat label="Monthly recurring revenue" :value="money($mrr)" icon="trending" tone="brand" hint="From active subscriptions" />
        <x-stat label="Collected this month" :value="money($collectedThisMonth)" icon="banknotes" tone="success"
            :hint="$change === null ? 'Nothing collected last month' : ($change >= 0 ? '▲ ' : '▼ ').abs($change).'% vs last month'" />
        <x-stat label="Outstanding" :value="money($unpaid)" icon="credit-card" tone="danger" :hint="$unpaidCount.' unpaid '.\Illuminate\Support\Str::plural('invoice', $unpaidCount)" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['inbox', 'New leads', $newDemoRequests, route('admin.demo-requests.index', ['status' => 'new']), 'leads'],
            ['chat', 'Open tickets', $openTickets, route('admin.tickets.index', ['status' => 'open']), 'tickets'],
            ['check-circle', 'Trial → paid (90 days)', $conversion === null ? '—' : $conversion.'%', route('admin.reports'), 'reports'],
        ] as [$icon, $label, $value, $href, $perm])
            @can($perm)
                <a href="{{ $href }}" class="card card-hover flex items-center gap-3 px-5 py-4">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-canvas text-steel"><x-icon :name="$icon" /></span>
                    <span class="min-w-0 flex-1 truncate text-sm text-body">{{ $label }}</span>
                    <span class="font-display tabular text-xl text-ink">{{ $value }}</span>
                </a>
            @endcan
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Revenue collected</h2>
                    <p class="text-sm text-muted">Last 12 months · {{ money(array_sum(array_column($revenue, 'value'))) }}</p>
                </div>
                @can('reports')<a href="{{ route('admin.reports') }}" class="text-sm font-medium text-brand-text hover:underline">Full report</a>@endcan
            </div>
            <div class="card-body pt-10">
                <x-bar-chart :series="$revenue" money />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Schools by status</h2></div>
            <div class="card-body space-y-4">
                @foreach (\App\Enums\SchoolStatus::cases() as $status)
                    @php $n = (int) ($counts[$status->value] ?? 0); $pct = $total ? round($n / $total * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="badge {{ $status->badge() }}">{{ $status->label() }}</span>
                            <span class="tabular font-medium text-ink">{{ $n }} <span class="font-normal text-muted">({{ $pct }}%)</span></span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-chip" role="presentation">
                            <div class="h-full rounded-full {{ $status->value === 'active' ? 'bg-success' : ($status->value === 'trial' ? 'bg-info' : ($status->value === 'suspended' ? 'bg-danger' : 'bg-warning')) }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
                @if ($planMix->isNotEmpty())
                    <div class="border-t border-line pt-4">
                        <p class="text-xs font-semibold tracking-wide text-muted uppercase">By plan</p>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach ($planMix as $plan)
                                <li class="flex items-center justify-between gap-2"><span class="truncate text-body">{{ $plan->name }}</span><span class="tabular font-semibold text-ink">{{ $plan->schools_count }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section id="renewals" class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Renewals due (next 30 days)</h2>
                <a href="{{ route('admin.schools.index') }}" class="text-sm font-medium text-brand-text hover:underline">All schools</a>
            </div>
            @if ($expiringSoon->isEmpty())
                <div class="card-body flex flex-col items-center py-12 text-center">
                    <span class="grid size-12 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="6" /></span>
                    <p class="mt-3 font-medium text-ink">Nothing expiring soon</p>
                    <p class="text-sm text-muted">Trials and subscriptions ending within 30 days will show here.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>School</th><th>Plan</th><th>Status</th><th>Ends</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($expiringSoon as $school)
                                <tr>
                                    <td><a href="{{ route('admin.schools.show', $school) }}" class="font-medium text-ink hover:text-brand-text">{{ $school->name }}</a></td>
                                    <td>{{ $school->plan?->name ?? '—' }}</td>
                                    <td>@include('admin.partials.school-status')</td>
                                    <td class="tabular">{{ $school->accessEndsAt()?->format('d M Y') }}</td>
                                    <td class="text-right"><a href="{{ route('admin.schools.show', $school) }}" class="btn btn-secondary btn-sm">Renew</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">New schools</h2>
                    <p class="text-sm text-muted">Last 12 months · {{ (int) array_sum(array_column($growth, 'value')) }}</p>
                </div>
            </div>
            <div class="card-body pt-10"><x-bar-chart :series="$growth" tone="info" height="h-32" compact /></div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Recently added schools</h2>
                @can('schools.manage')<a href="{{ route('admin.schools.create') }}" class="btn btn-secondary btn-sm"><x-icon name="plus" size="4" /> Add school</a>@endcan
            </div>
            @if ($recentSchools->isEmpty())
                <div class="card-body flex flex-col items-center py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="building" size="7" /></span>
                    <p class="mt-4 text-lg font-semibold text-ink">Add your first school</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Create a school, pick a plan and Skooline sets up its portal and admin login automatically.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recentSchools as $school)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-school-badge :school="$school" size="sm" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.schools.show', $school) }}" class="block truncate font-medium text-ink hover:text-brand-text">{{ $school->name }}</a>
                                <p class="truncate text-xs text-muted">{{ $school->displayUrl() }} · {{ $school->plan?->name ?? 'No plan' }}</p>
                            </div>
                            <div class="hidden sm:block">@include('admin.partials.school-status')</div>
                            <span class="tabular hidden shrink-0 text-xs text-muted md:block">{{ $school->created_at->format('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @can('activity')
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent activity</h2>
                    <a href="{{ route('admin.activity') }}" class="text-sm font-medium text-brand-text hover:underline">View all</a>
                </div>
                @include('admin.partials.activity-feed', ['items' => $activity])
            </section>
        @endcan
    </div>
</x-layouts.admin>
