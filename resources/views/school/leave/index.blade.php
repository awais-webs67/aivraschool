<x-layouts.school heading="Leave" :subheading="$canApprove ? 'Approve staff leave and see who is away.' : 'Apply for leave and see your requests.'">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($canApprove)
                <nav class="flex gap-1 overflow-x-auto border-b border-line" aria-label="Leave">
                    @foreach (['pending' => 'Waiting'.($pending ? ' ('.$pending.')' : ''), 'all' => 'All requests', 'mine' => 'My leave'] as $key => $label)
                        <a href="{{ route('school.leave.index', ['tab' => $key]) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab === $key ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            <section class="card">
                @if ($requests->isEmpty())
                    <p class="px-5 py-14 text-center text-sm text-muted">{{ $tab === 'pending' ? 'No leave waiting for approval.' : 'No leave requests yet.' }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($requests as $leave)
                            <li class="px-5 py-4">
                                <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
                                    <div class="min-w-[12rem] flex-1">
                                        <p class="flex flex-wrap items-center gap-2"><span class="font-semibold text-ink">{{ $leave->staff?->name }}</span><span class="badge {{ $leave->statusBadge() }}">{{ ucfirst($leave->status) }}</span></p>
                                        <p class="text-sm text-body">{{ \App\Models\LeaveRequest::TYPES[$leave->type] ?? $leave->type }} · {{ $leave->period() }} · {{ $leave->days }} {{ \Illuminate\Support\Str::plural('day', $leave->days) }}</p>
                                        <p class="mt-1 text-sm break-words whitespace-pre-line text-muted">{{ $leave->reason }}</p>
                                        @if ($leave->reviewed_at)<p class="mt-1 text-xs text-muted">{{ ucfirst($leave->status) }} by {{ $leave->reviewer?->name ?? 'admin' }} {{ $leave->reviewed_at->diffForHumans() }}{{ $leave->review_note ? ': '.$leave->review_note : '' }}</p>@endif
                                    </div>
                                    @if ($leave->status === 'pending' && $canApprove && $leave->staff_id !== $staff?->id)
                                        <form method="POST" action="{{ route('school.leave.review', $leave) }}" class="flex flex-wrap items-center gap-2">
                                            @csrf
                                            <input type="text" name="review_note" placeholder="Note (optional)" class="input w-40 py-1.5 text-sm" maxlength="255" aria-label="Note">
                                            <button name="decision" value="rejected" class="btn btn-danger-outline btn-sm">Reject</button>
                                            <button name="decision" value="approved" class="btn btn-primary btn-sm">Approve</button>
                                        </form>
                                    @elseif ($leave->status === 'pending' && $leave->staff_id === $staff?->id)
                                        <form method="POST" action="{{ route('school.leave.destroy', $leave) }}" data-confirm="Withdraw this request?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm">Withdraw</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    @if ($requests->hasPages())<div class="border-t border-line px-4 py-3">{{ $requests->links() }}</div>@endif
                @endif
            </section>
        </div>

        <div class="space-y-6">
            @if ($canApprove)
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Away today</h2></div>
                    <ul class="divide-y divide-line">
                        @forelse ($onLeaveToday as $l)
                            <li class="px-5 py-3 text-sm"><span class="font-medium text-ink">{{ $l->staff?->name }}</span> <span class="text-muted">· until {{ $l->to_date->format('d M') }}</span></li>
                        @empty
                            <li class="px-5 py-4 text-sm text-muted">Everyone is in.</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            @if ($staff)
                <form method="POST" action="{{ route('school.leave.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">Apply for leave</h2></div>
                    <div class="card-body space-y-4">
                        <x-form.select name="type" label="Type" :options="\App\Models\LeaveRequest::TYPES" value="casual" required />
                        <div class="grid grid-cols-2 gap-3">
                            <x-form.input name="from_date" type="date" label="From" :value="today()->toDateString()" required />
                            <x-form.input name="to_date" type="date" label="To" :value="today()->toDateString()" required />
                        </div>
                        <x-form.textarea name="reason" label="Reason" rows="3" required maxlength="1000" />
                        <button class="btn btn-primary w-full">Send request</button>
                    </div>
                </form>
            @elseif (! $canApprove)
                <div class="card p-5 text-sm text-muted">Your login is not linked to a staff record, so you can't apply for leave here. Ask the school admin.</div>
            @endif
        </div>
    </div>
</x-layouts.school>
