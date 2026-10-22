<x-layouts.school heading="Dashboard" :subheading="'Welcome, '.$user->name.' · '.today()->format('l, d M Y')">
    @php $remaining = collect($setup)->where('done', false)->count(); @endphp

    @foreach ($announcements as $a)
        <div class="alert alert-{{ $a->tone }} mb-4" role="status">
            <x-icon :name="$a->tone === 'danger' || $a->tone === 'warning' ? 'alert' : 'bolt'" class="mt-px" />
            <div class="min-w-0">
                <p class="font-semibold">{{ $a->title }}</p>
                <p class="mt-0.5 whitespace-pre-line opacity-90">{{ $a->body }}</p>
                <p class="mt-1 text-xs opacity-70">From AivraSol · {{ $a->created_at->format('d M Y') }}</p>
            </div>
        </div>
    @endforeach

    @if ($setup && $remaining > 0)
        <section class="card mb-6 border-brand/40">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Set up your school</h2>
                    <p class="text-sm text-muted">{{ count($setup) - $remaining }} of {{ count($setup) }} steps done</p>
                </div>
                <div class="h-2 w-40 overflow-hidden rounded-full bg-chip" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($setup) }}" aria-valuenow="{{ count($setup) - $remaining }}">
                    <div class="h-full rounded-full bg-brand" style="width: {{ round((count($setup) - $remaining) / count($setup) * 100) }}%"></div>
                </div>
            </div>
            <ol class="divide-y divide-line">
                @foreach ($setup as $i => $step)
                    <li>
                        <a href="{{ route($step['route']) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-canvas">
                            @if ($step['done'])
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-success text-white"><x-icon name="check" size="4" /></span>
                            @else
                                <span class="grid size-8 shrink-0 place-items-center rounded-full border-2 border-line-strong text-sm font-semibold text-muted">{{ $i + 1 }}</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium {{ $step['done'] ? 'text-muted line-through' : 'text-ink' }}">{{ $step['label'] }}</span>
                                <span class="block text-xs text-muted">{{ $step['hint'] }}</span>
                            </span>
                            <x-icon name="chevron-right" size="4" class="text-muted" />
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($overview)
        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <x-stat label="Students" :value="number_format($overview['students'])" icon="users" tone="brand" :hint="$overview['newAdmissions'].' new this month'" />
            <x-stat label="Present today" :value="$overview['attendance'] !== null ? $overview['attendance'].'%' : 'Not marked'" icon="check-circle" :tone="$overview['attendance'] === null ? 'neutral' : ($overview['attendance'] >= 85 ? 'success' : 'warning')" :hint="$overview['attendance'] !== null ? $overview['absentToday'].' absent' : null" />
            <x-stat label="Staff in today" :value="($overview['staff'] - $overview['staffAway']).' / '.$overview['staff']" icon="briefcase" tone="info" :hint="$overview['staffAway'] ? $overview['staffAway'].' absent or on leave' : null" />
            @if ($fees)
                <x-stat label="Fees this month" :value="money($fees['month'])" icon="banknotes" tone="success" :hint="$fees['billed'] > 0 ? round($fees['month'] / $fees['billed'] * 100).'% of '.money($fees['billed']) : money($fees['today']).' today'" />
            @else
                <x-stat label="Session" :value="$session?->name ?? 'Not set'" icon="calendar" tone="neutral" />
            @endif
        </div>

        @if ($overview['alerts'])
            <section class="card mt-6">
                <div class="card-header"><h2 class="card-title">Needs your attention</h2></div>
                <ul class="divide-y divide-line">
                    @foreach ($overview['alerts'] as [$icon, $tone, $text, $url])
                        <li><a href="{{ $url }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full {{ ['warning' => 'bg-warning-tint text-warning', 'danger' => 'bg-danger-tint text-danger', 'brand' => 'bg-brand-tint text-brand-text', 'info' => 'bg-info-tint text-info', 'neutral' => 'bg-chip text-steel'][$tone] }}"><x-icon :name="$icon" size="4" /></span>
                            <span class="min-w-0 flex-1 text-sm font-medium text-ink">{{ $text }}</span>
                            <x-icon name="chevron-right" size="4" class="text-muted" />
                        </a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Attendance, last school days</h2></div>
                <div class="card-body">
                    @if ($overview['trend']->isEmpty())
                        <p class="text-sm text-muted">No attendance marked yet.</p>
                    @else
                        <div class="flex h-36 items-end gap-1.5" role="img" aria-label="Attendance trend">
                            @foreach ($overview['trend'] as $t)
                                <div class="flex min-w-0 flex-1 flex-col items-center gap-1" title="{{ $t['date']->format('d M') }}: {{ $t['percent'] }}%">
                                    <span class="text-[10px] text-muted tabular">{{ $t['percent'] }}</span>
                                    <div class="w-full rounded-t {{ $t['percent'] >= 85 ? 'bg-success' : ($t['percent'] >= 70 ? 'bg-warning' : 'bg-danger') }}" style="height: {{ max(4, $t['percent']) }}%"></div>
                                    <span class="text-[10px] text-muted">{{ $t['date']->format('d') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Students by class</h2></div>
                <ul class="card-body space-y-2">
                    @php $max = max(1, (int) $overview['strength']->max('n')); @endphp
                    @forelse ($overview['strength'] as $row)
                        <li class="grid grid-cols-[6.5rem_1fr_2.5rem] items-center gap-2 text-sm">
                            <span class="truncate text-body">{{ $row->name }}</span>
                            <span class="h-2.5 overflow-hidden rounded-full bg-chip"><span class="block h-full rounded-full bg-brand" style="width: {{ round($row->n / $max * 100) }}%"></span></span>
                            <span class="text-right font-semibold tabular text-ink">{{ $row->n }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-muted">No students yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @endif

    @if ($teaching)
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">My lessons today</h2>@if (Route::has('school.timetable.index') && $user->allows('timetable.view'))<a href="{{ route('school.timetable.index') }}" class="text-sm font-semibold text-brand-text">Full timetable</a>@endif</div>
                <ul class="divide-y divide-line">
                    @forelse ($teaching['lessons'] as $l)
                        <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                            <span class="w-24 shrink-0 text-xs text-muted tabular">{{ $l['entry']?->period?->timeRange() }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $l['entry']?->subject?->name ?? 'Lesson' }} · {{ $l['entry']?->section?->label() }}</span>
                            @if ($l['cover'])<span class="badge badge-warning">Cover</span>@endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-muted">No lessons in the timetable today.</li>
                    @endforelse
                </ul>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">My classes</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($teaching['classOf'] as [$section, $done])
                        <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                            <span class="min-w-0 flex-1 font-medium text-ink">{{ $section->label() }}</span>
                            @if ($done)
                                <span class="badge badge-success">Attendance done</span>
                            @elseif ($user->allows('attendance.take'))
                                <a href="{{ route('school.attendance.mark', $section) }}" class="btn btn-primary btn-sm">Take attendance</a>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-muted">You are not a class teacher.</li>
                    @endforelse
                </ul>
                @if ($teaching['myLeave'])
                    <p class="border-t border-line px-5 py-3 text-xs text-muted">Last leave request: {{ $teaching['myLeave']->period() }} · <span class="badge {{ $teaching['myLeave']->statusBadge() }}">{{ ucfirst($teaching['myLeave']->status) }}</span></p>
                @endif
            </section>
        </div>
    @endif

    @if ($fees && ! $overview)
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <x-stat label="Collected today" :value="money($fees['today'])" icon="banknotes" tone="success" />
            <x-stat label="This month" :value="money($fees['month'])" icon="chart" tone="brand" />
            <x-stat label="Billed this month" :value="money($fees['billed'])" icon="credit-card" tone="neutral" />
            <x-stat label="Still to collect" :value="money($fees['outstanding'])" icon="clock" tone="warning" />
        </div>
    @endif

    @if ($office && ! $overview)
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
            <x-stat label="Visitors today" :value="$office['visitors']" icon="users" tone="info" />
            <x-stat label="New enquiries today" :value="$office['enquiries']" icon="clipboard" tone="brand" />
            <x-stat label="Follow-ups due" :value="$office['followUps']" icon="clock" tone="warning" />
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Notices</h2>@if (Route::has('school.notices.index'))<a href="{{ route('school.notices.index') }}" class="text-sm font-semibold text-brand-text">All notices</a>@endif</div>
            <ul class="divide-y divide-line">
                @forelse ($notices as $n)
                    <li class="px-5 py-3"><p class="text-sm font-medium text-ink">{{ $n->title }}</p><p class="line-clamp-2 text-xs text-muted">{{ $n->body }}</p></li>
                @empty
                    <li class="px-5 py-6 text-sm text-muted">No notices.</li>
                @endforelse
            </ul>
        </section>
        <section class="card">
            <div class="card-header"><h2 class="card-title">Birthdays today</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($birthdays as [$name, $what])
                    <li class="flex items-center gap-3 px-5 py-2.5 text-sm"><span aria-hidden="true">🎂</span><span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $name }}</span><span class="text-xs text-muted">{{ $what }}</span></li>
                @empty
                    <li class="px-5 py-6 text-sm text-muted">No birthdays today.</li>
                @endforelse
            </ul>
        </section>
    </div>

    @if ($user->allows('setup'))
        <section class="card mt-6">
            <div class="card-header">
                <h2 class="card-title">Your modules</h2>
                <span class="text-xs text-muted">What your plan includes.</span>
            </div>
            <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($modules as $module)
                    <div class="flex items-start gap-3 rounded border p-4 {{ $module['enabled'] ? 'border-line bg-white' : 'border-dashed border-line bg-canvas' }}">
                        <span class="grid size-10 shrink-0 place-items-center rounded-md {{ $module['enabled'] ? 'bg-brand-tint text-brand-text' : 'bg-chip text-muted' }}"><x-icon :name="$module['icon']" /></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold {{ $module['enabled'] ? 'text-ink' : 'text-muted' }}">{{ $module['label'] }}</p>
                            <p class="text-xs text-muted">{{ $module['enabled'] ? $module['description'] : 'Not in your plan. Contact AivraSol to upgrade.' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.school>
