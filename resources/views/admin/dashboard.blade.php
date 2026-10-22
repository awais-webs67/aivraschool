<x-layouts.admin heading="Dashboard" :subheading="'Welcome back, '.auth()->user()->name.'. Here is how Skooline is doing today.'">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total schools" :value="number_format($total)" icon="building" tone="neutral"
            :hint="($counts['active'] ?? 0).' active · '.($counts['trial'] ?? 0).' on trial'" />
        <x-stat label="Monthly recurring revenue" :value="money($mrr)" icon="chart" tone="brand" hint="From active subscriptions" />
        <x-stat label="Collected this month" :value="money($collectedThisMonth)" icon="banknotes" tone="success"
            :hint="'Unpaid invoices: '.money($unpaid)" />
        <x-stat label="New demo requests" :value="number_format($newDemoRequests)" icon="inbox" tone="info" hint="Leads from the website" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
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
            <div class="card-header"><h2 class="card-title">Schools by status</h2></div>
            <div class="card-body space-y-4">
                @foreach (\App\Enums\SchoolStatus::cases() as $status)
                    @php $n = (int) ($counts[$status->value] ?? 0); $pct = $total ? round($n / $total * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="badge {{ $status->badge() }}">{{ $status->label() }}</span>
                            <span class="tabular font-medium text-ink">{{ $n }} <span class="font-normal text-muted">({{ $pct }}%)</span></span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-chip" role="presentation">
                            <div class="h-full rounded-full {{ $status->value === 'active' ? 'bg-success' : ($status->value === 'trial' ? 'bg-info' : ($status->value === 'suspended' ? 'bg-danger' : 'bg-warning')) }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Recently added schools</h2>
            <a href="{{ route('admin.schools.create') }}" class="btn btn-secondary btn-sm"><x-icon name="plus" size="4" /> Add school</a>
        </div>
        @if ($recentSchools->isEmpty())
            <div class="card-body flex flex-col items-center py-14 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="building" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">Add your first school</p>
                <p class="mt-1 max-w-sm text-sm text-muted">Create a school, pick a plan and Skooline sets up its own database and admin login automatically.</p>
                <a href="{{ route('admin.schools.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" size="4" /> Add school</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>School</th><th>City</th><th>Plan</th><th>Status</th><th>Added</th></tr></thead>
                    <tbody>
                        @foreach ($recentSchools as $school)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.schools.show', $school) }}" class="font-medium text-ink hover:text-brand-text">{{ $school->name }}</a>
                                    <p class="text-xs text-muted">{{ $school->host() }}</p>
                                </td>
                                <td>{{ $school->city ?? '—' }}</td>
                                <td>{{ $school->plan?->name ?? '—' }}</td>
                                <td>@include('admin.partials.school-status')</td>
                                <td class="tabular">{{ $school->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
