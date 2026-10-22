<x-layouts.school heading="Approvals" subheading="Everything waiting for you.">
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Leave requests</h2><span class="badge {{ $leave->isEmpty() ? 'badge-success' : 'badge-warning' }}">{{ $leave->count() }}</span></div>
            <ul class="divide-y divide-line">
                @forelse ($leave as $l)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3">
                        <div class="min-w-[12rem] flex-1">
                            <p class="font-semibold text-ink">{{ $l->staff?->name }}</p>
                            <p class="text-sm text-body">{{ \App\Models\LeaveRequest::TYPES[$l->type] ?? $l->type }} · {{ $l->period() }} · {{ $l->days }} {{ str('day')->plural($l->days) }}</p>
                            <p class="text-xs text-muted">{{ $l->reason }}</p>
                        </div>
                        @if ($l->staff_id !== auth('school')->user()->staff?->id)
                            <form method="POST" action="{{ route('school.leave.review', $l) }}" class="flex gap-2">
                                @csrf
                                <button name="decision" value="rejected" class="btn btn-danger-outline btn-sm">Reject</button>
                                <button name="decision" value="approved" class="btn btn-primary btn-sm">Approve</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">No leave waiting. 🎉</li>
                @endforelse
            </ul>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Serious behaviour (14 days)</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($serious as $i)
                    <li class="px-5 py-3 text-sm"><a href="{{ route('school.students.show', $i->student_id) }}" class="font-semibold text-ink hover:text-brand-text">{{ $i->student?->name }}</a> <span class="text-xs text-muted">{{ $i->student?->classLabel() }} · {{ $i->date->format('d M') }}</span><p class="text-body">{{ $i->category }}: {{ $i->description }}</p></li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">Nothing serious reported.</li>
                @endforelse
            </ul>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Open complaints</h2>@if (auth('school')->user()->allows('front_office'))<a href="{{ route('school.visitors.complaints') }}" class="text-sm font-semibold text-brand-text">Open register</a>@endif</div>
            <ul class="divide-y divide-line">
                @forelse ($complaints as $c)
                    <li class="px-5 py-3 text-sm"><p class="font-semibold text-ink">{{ $c->from_name }} <span class="text-xs font-normal text-muted">· {{ $c->created_at->diffForHumans() }}</span></p><p class="line-clamp-2 text-body">{{ $c->details }}</p></li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">No open complaints.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.school>
