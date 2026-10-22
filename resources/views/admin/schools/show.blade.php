@php
    $latest = $school->subscriptions->first();
    $methods = collect(\App\Models\Subscription::PAYMENT_METHODS)->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Str::headline($m)]);
@endphp
<x-layouts.admin :title="$school->name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-4">
            @if ($school->photo_path)
                <img src="{{ route('admin.schools.photo', [$school, 'school']) }}" alt="{{ $school->name }}" class="size-14 shrink-0 rounded-lg object-cover ring-1 ring-line">
            @else
                <span class="font-display grid size-14 shrink-0 place-items-center rounded-lg bg-ink text-2xl text-white">{{ strtoupper(mb_substr($school->name, 0, 1)) }}</span>
            @endif
            <div class="min-w-0">
                <a href="{{ route('admin.schools.index') }}" class="text-xs font-medium text-muted hover:text-ink">← Schools</a>
                <h1 class="page-title truncate">{{ $school->name }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    @include('admin.partials.school-status')
                    <a href="{{ $school->url('login') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium text-brand-text hover:underline">{{ $school->displayUrl() }} <x-icon name="external" size="3" /></a>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('impersonate')
                <form method="POST" action="{{ route('admin.schools.impersonate', $school) }}">
                    @csrf
                    <button class="btn btn-dark" @disabled(! $school->canAccess()) title="Open the school portal as its admin"><x-icon name="login-as" size="4" /> Login as school</button>
                </form>
            @endcan
            @can('schools.manage')
            <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-secondary"><x-icon name="pencil" size="4" /> Edit</a>
            @if ($school->status->value === 'suspended')
                <form method="POST" action="{{ route('admin.schools.activate', $school) }}">
                    @csrf
                    <button class="btn btn-dark"><x-icon name="refresh" size="4" /> Reactivate</button>
                </form>
            @else
                <button type="button" class="btn btn-danger-outline" data-toggle="#suspend-form"><x-icon name="ban" size="4" /> Suspend</button>
            @endif
            @endcan
        </div>
    </div>

    <div class="mb-6 space-y-3 empty:hidden">
        @if ($school->status->value === 'suspended')
            <div class="alert alert-danger"><x-icon name="ban" class="mt-px" />
                <p>Suspended {{ $school->suspended_at?->diffForHumans() }}.@if ($school->suspension_reason) Reason: {{ $school->suspension_reason }}@endif Nobody from this school can sign in.</p>
            </div>
        @elseif ($school->inGracePeriod())
            <div class="alert alert-warning"><x-icon name="clock" class="mt-px" />
                <p>Subscription ended on {{ $school->accessEndsAt()->format('d M Y') }}. The school is in its {{ config('skooline.grace_days') }}-day grace period. Add a subscription to avoid interruption.</p>
            </div>
        @elseif (! $school->canAccess())
            <div class="alert alert-warning"><x-icon name="clock" class="mt-px" />
                <p>Access has ended. Add a subscription below to restore the school's portal.</p>
            </div>
        @endif
    </div>

    <form id="suspend-form" hidden method="POST" action="{{ route('admin.schools.suspend', $school) }}" class="card mb-6 border-danger/30 p-5" data-confirm="Suspend {{ $school->name }}? Nobody from this school will be able to sign in.">
        @csrf
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <x-form.input name="reason" label="Reason for suspension (optional)" class="flex-1" placeholder="e.g. Payment overdue" />
            <button class="btn btn-danger">Suspend school</button>
        </div>
    </form>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Subscription</h2>
                    @can('billing')<button type="button" class="btn btn-primary btn-sm" data-toggle="#subscription-form"><x-icon name="plus" size="4" /> Add / renew</button>@endcan
                </div>

                <form id="subscription-form" method="POST" action="{{ route('admin.schools.subscriptions.store', $school) }}"
                    class="grid gap-4 border-b border-line bg-canvas/60 p-5 sm:grid-cols-2" @unless ($errors->hasAny(['plan_id', 'starts_at', 'ends_at', 'amount', 'payment_status'])) hidden @endunless>
                    @csrf
                    <x-form.select name="plan_id" label="Plan" required :value="$school->plan_id" :options="$plans->mapWithKeys(fn ($p) => [$p->id => $p->name.' — '.$p->billingLabel().' ('.money($p->price).($p->isPerStudent() ? ' + '.money($p->price_per_student).'/student' : '').')'])" class="sm:col-span-2" />
                    <x-form.input name="starts_at" type="date" label="Start date" required :value="($school->subscription_ends_at && $school->subscription_ends_at->isFuture() ? $school->subscription_ends_at->copy()->addDay() : now())->toDateString()" />
                    <x-form.input name="ends_at" type="date" label="End date" hint="Blank = calculated from the plan (lifetime has no end)." />
                    <x-form.input name="student_count" type="number" min="0" label="Students" :value="$latest?->student_count" hint="For per-student pricing." />
                    <x-form.input name="amount" type="number" min="0" step="0.01" label="Amount ({{ \App\Models\Setting::currency() }})" hint="Blank = plan price." />
                    <x-form.select name="payment_status" label="Payment status" required :options="['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partially paid', 'waived' => 'Waived']" value="unpaid" />
                    <x-form.select name="payment_method" label="Payment method" :options="$methods" placeholder="—" />
                    <x-form.input name="payment_reference" label="Reference / transaction ID" class="sm:col-span-2" />
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" class="btn btn-ghost" data-toggle="#subscription-form">Cancel</button>
                        <button class="btn btn-primary">Save subscription</button>
                    </div>
                </form>

                <div class="card-body grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted">Current plan</p>
                        <p class="mt-0.5 font-semibold text-ink">{{ $school->plan?->name ?? '—' }}</p>
                        <p class="text-xs text-muted">{{ $school->plan?->billingLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">{{ $school->status->value === 'trial' ? 'Trial ends' : 'Access until' }}</p>
                        <p class="tabular mt-0.5 font-semibold text-ink">{{ $school->accessEndsAt()?->format('d M Y') ?? ($school->status->value === 'active' ? 'Lifetime' : '—') }}</p>
                        @if (($days = $school->daysLeft()) !== null)
                            <p class="text-xs {{ $days < 0 ? 'text-danger' : 'text-muted' }}">{{ $days < 0 ? abs($days).' '.\Illuminate\Support\Str::plural('day', abs($days)).' ago' : $days.' '.\Illuminate\Support\Str::plural('day', $days).' left' }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-muted">Last invoice</p>
                        <p class="tabular mt-0.5 font-semibold text-ink">{{ $latest ? money($latest->amount) : '—' }}</p>
                        @if ($latest)<span class="badge {{ $latest->payment_status === 'paid' ? 'badge-success' : ($latest->payment_status === 'unpaid' ? 'badge-danger' : 'badge-neutral') }}">{{ ucfirst($latest->payment_status) }}</span>@endif
                    </div>
                </div>

                @if ($school->status->value === 'trial' && auth()->user()->can('schools.manage'))
                    <form method="POST" action="{{ route('admin.schools.extend-trial', $school) }}" class="flex flex-wrap items-end gap-3 border-t border-line px-5 py-4">
                        @csrf
                        <x-form.input name="days" type="number" min="1" max="365" value="7" label="Extend trial by (days)" class="w-44" />
                        <button class="btn btn-secondary">Extend trial</button>
                    </form>
                @endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Billing history</h2></div>
                @if ($school->subscriptions->isEmpty())
                    <p class="card-body text-sm text-muted">No subscriptions yet{{ $school->status->value === 'trial' ? ' — the school is on a free trial.' : '.' }}</p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Invoice</th><th>Period</th><th>Amount</th><th>Payment</th><th class="text-right"></th></tr></thead>
                            <tbody>
                                @foreach ($school->subscriptions as $subscription)
                                    <tr>
                                        <td><p class="font-mono text-xs font-semibold text-ink">{{ $subscription->invoice_number }}</p><p class="text-xs text-muted">{{ $subscription->plan_name }}@if ($subscription->student_count) · {{ $subscription->student_count }} students @endif</p></td>
                                        <td class="tabular text-xs">{{ $subscription->starts_at->format('d M Y') }} – {{ $subscription->ends_at?->format('d M Y') ?? 'Lifetime' }}</td>
                                        <td class="tabular font-medium text-ink">{{ money($subscription->amount) }}</td>
                                        <td>
                                            <span class="badge {{ $subscription->paymentBadge() }}">{{ ucfirst($subscription->payment_status) }}</span>
                                            @if ($subscription->payment_method)<span class="text-xs text-muted"> · {{ \Illuminate\Support\Str::headline($subscription->payment_method) }}</span>@endif
                                        </td>
                                        <td class="text-right">
                                            @can('billing')
                                            <div class="inline-flex flex-wrap justify-end gap-1">
                                            <a href="{{ route('admin.subscriptions.invoice', $subscription) }}" target="_blank" class="btn btn-ghost btn-sm" title="Print invoice"><x-icon name="printer" size="4" /><span class="sr-only">Print invoice</span></a>
                                            @if ($subscription->payment_status !== 'paid')
                                                <form method="POST" action="{{ route('admin.subscriptions.paid', $subscription) }}" class="inline-flex gap-1">
                                                    @csrf
                                                    <select name="payment_method" class="input w-36 py-1.5 text-xs" aria-label="Payment method">
                                                        @foreach ($methods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                                    </select>
                                                    <button class="btn btn-secondary btn-sm">Mark paid</button>
                                                </form>
                                            @endif
                                            </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Modules in this plan</h2>
                    <a href="{{ $school->plan ? route('admin.plans.edit', $school->plan) : route('admin.plans.index') }}" class="text-sm font-medium text-brand-text hover:underline">Edit plan</a>
                </div>
                <div class="card-body grid gap-2 sm:grid-cols-2">
                    @foreach (config('skooline.modules') as $key => $module)
                        @php $on = $school->hasModule($key); @endphp
                        <div class="flex items-center gap-2 text-sm {{ $on ? 'text-ink' : 'text-muted line-through decoration-line-strong' }}">
                            <span class="grid size-5 place-items-center rounded-full {{ $on ? 'bg-success-tint text-success' : 'bg-chip text-muted' }}"><x-icon :name="$on ? 'check' : 'x'" size="3" /></span>
                            {{ $module['label'] }}
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Usage</h2>
                    <span class="text-xs text-muted">{{ $lastLogin ? 'Last sign-in '.\Illuminate\Support\Carbon::parse($lastLogin)->diffForHumans() : 'Nobody has signed in yet' }}</span>
                </div>
                <div class="card-body grid grid-cols-2 gap-3">
                    @foreach ($usage as $label => $n)
                        <div class="rounded-md bg-canvas px-3 py-2.5">
                            <p class="text-xs text-muted">{{ $label }}</p>
                            <p class="font-display tabular text-xl text-ink">{{ number_format($n) }}</p>
                        </div>
                    @endforeach
                    <div class="col-span-2 rounded-md bg-canvas px-3 py-2.5">
                        <p class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="sparkles" size="3" /> AI questions this month</p>
                        <p class="font-display tabular text-xl text-ink">{{ number_format($aiUsed) }} <span class="text-sm font-normal text-muted">/ {{ $school->aiMonthlyLimit() ?: 'no AI' }}</span></p>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">School admins</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($admins as $admin)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid size-9 place-items-center rounded-full bg-steel text-sm font-semibold text-white">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $admin->name }}</p>
                                <p class="truncate text-xs text-muted">{{ $admin->email }} · {{ $admin->last_login_at ? 'last login '.$admin->last_login_at->diffForHumans() : 'never signed in' }}</p>
                            </div>
                            @can('schools.manage')
                            <form method="POST" action="{{ route('admin.schools.reset-password', $school) }}" data-confirm="Generate a new password for {{ $admin->email }}?">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $admin->id }}">
                                <button class="btn btn-ghost btn-sm" title="Reset password"><x-icon name="key" size="4" /><span class="sr-only">Reset password</span></button>
                            </form>
                            @endcan
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-muted">No admin accounts.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Principal</h2></div>
                <div class="card-body flex gap-4">
                    @if ($school->principal_photo_path)
                        <img src="{{ route('admin.schools.photo', [$school, 'principal']) }}" alt="{{ $school->principal_name }}" class="size-16 shrink-0 rounded-lg object-cover ring-1 ring-line">
                    @endif
                    <dl class="min-w-0 flex-1 space-y-2 text-sm">
                        @foreach (['Name' => $school->principal_name, 'Phone' => $school->principal_phone, 'Email' => $school->principal_email, 'National ID' => $school->principal_national_id, 'Qualification' => $school->principal_qualification, 'Address' => $school->principal_address] as $label => $value)
                            <div class="grid grid-cols-[6.5rem_1fr] gap-2"><dt class="text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value ?: '—' }}</dd></div>
                        @endforeach
                    </dl>
                </div>
                @if ($school->principal_bio)<p class="border-t border-line px-5 py-4 text-sm whitespace-pre-line text-body">{{ $school->principal_bio }}</p>@endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Details</h2></div>
                <dl class="card-body space-y-3 text-sm">
                    @foreach (['Email' => $school->email, 'Phone' => $school->phone, 'City' => collect([$school->city, $school->country])->filter()->join(', '), 'Fee currency' => $school->currencyCode(), 'Address' => $school->address, 'Created' => $school->created_at->format('d M Y, h:i A'), 'School ID' => $school->id] as $label => $value)
                        <div class="grid grid-cols-[7.5rem_1fr] gap-2">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="break-words text-ink">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($school->notes)
                    <div class="border-t border-line px-5 py-4 text-sm">
                        <p class="text-xs font-semibold text-muted uppercase">Internal notes</p>
                        <p class="mt-1 whitespace-pre-line text-body">{{ $school->notes }}</p>
                    </div>
                @endif
            </section>

            @can('tickets')
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Support tickets</h2></div>
                    @if ($tickets->isEmpty())
                        <p class="card-body text-sm text-muted">No tickets from this school.</p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($tickets as $ticket)
                                <li><a href="{{ route('admin.tickets.show', $ticket->id) }}" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-canvas"><span class="min-w-0 flex-1 truncate text-ink">{{ $ticket->subject }}</span><span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportTicket::STATUSES[$ticket->status] }}</span></a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endcan

            @can('activity')
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Recent activity</h2></div>
                    @include('admin.partials.activity-feed', ['items' => $activity])
                </section>
            @endcan

            @can('schools.manage')
            <section class="card border-danger/30">
                <div class="card-header"><h2 class="card-title text-danger">Danger zone</h2></div>
                <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" class="card-body space-y-3" data-confirm="This permanently deletes {{ $school->name }} and ALL of its data. Continue?">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm text-body">Deleting removes all of this school's users, records and billing history permanently. This cannot be undone.</p>
                    <x-form.input name="confirm_slug" label="Type {{ $school->slug }} to confirm" autocomplete="off" />
                    <button class="btn btn-danger w-full"><x-icon name="trash" size="4" /> Delete school permanently</button>
                </form>
            </section>
            @endcan
        </div>
    </div>
</x-layouts.admin>
