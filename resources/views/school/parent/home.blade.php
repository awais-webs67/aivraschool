<x-layouts.school heading="My children" :subheading="'Welcome, '.auth('school')->user()->name.'.'">
    @if ($children->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No children are linked to your login yet. Please contact the school office.</div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($children as $c)
                @php $s = $c['student']; @endphp
                <a href="{{ route('school.parent.child', $s) }}" class="card block p-5 transition hover:border-brand/40">
                    <div class="flex items-center gap-4">
                        @include('school.students._avatar', ['s' => $s, 'size' => 'size-14 text-lg'])
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-lg font-semibold text-ink">{{ $s->name }}</p>
                            <p class="text-sm text-muted">{{ $s->classLabel() }}{{ $s->roll_no ? ' · Roll '.$s->roll_no : '' }}</p>
                        </div>
                        <x-icon name="chevron-right" size="5" class="text-muted" />
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 border-t border-line pt-4 text-center">
                        <div>
                            <dd class="font-display text-xl {{ $c['today'] === 'absent' ? 'text-danger' : 'text-ink' }}">{{ $c['today'] ? \App\Models\StudentAttendance::STATUSES[$c['today']] : '—' }}</dd>
                            <dt class="text-xs text-muted">Today</dt>
                        </div>
                        <div>
                            <dd class="font-display text-xl {{ $c['attendance']['percent'] !== null && $c['attendance']['percent'] < 75 ? 'text-danger' : 'text-ink' }}">{{ $c['attendance']['percent'] !== null ? $c['attendance']['percent'].'%' : '—' }}</dd>
                            <dt class="text-xs text-muted">This month</dt>
                        </div>
                        <div>
                            <dd class="font-display text-xl {{ $c['balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ $c['balance'] > 0 ? money($c['balance']) : 'Paid' }}</dd>
                            <dt class="text-xs text-muted">Fees due</dt>
                        </div>
                    </dl>
                    @if ($c['homework'])<p class="mt-3 text-xs font-medium text-brand-text">{{ $c['homework'] }} homework {{ str('entry')->plural($c['homework']) }} today</p>@endif
                </a>
            @endforeach
        </div>
    @endif

    <section class="card mt-6">
        <div class="card-header"><h2 class="card-title">School notices</h2></div>
        <ul class="divide-y divide-line">
            @forelse ($notices as $n)
                <li class="px-5 py-3">
                    <p class="flex flex-wrap items-center gap-2 font-medium text-ink">@if ($n->is_pinned)<x-icon name="bolt" size="4" class="text-brand" />@endif {{ $n->title }} <span class="text-xs font-normal text-muted">{{ $n->publish_on->format('d M') }}</span></p>
                    <p class="mt-0.5 text-sm break-words whitespace-pre-line text-body">{{ $n->body }}</p>
                    @if ($n->attachment_path)<a href="{{ route('school.notices.file', $n) }}" data-no-progress class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-text"><x-icon name="download" size="4" /> {{ $n->attachment_name }}</a>@endif
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-muted">No notices right now.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.school>
