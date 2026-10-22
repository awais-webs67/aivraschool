<x-layouts.school heading="Attendance" subheading="Staff attendance. Approved leave is filled in for you.">
    @include('school.attendance._tabs')
    <div class="mb-6">@include('school.attendance._date', ['route' => 'school.attendance.staff'])</div>

    @if ($staff->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">Add staff first. <a href="{{ route('school.staff.create') }}" class="font-semibold text-brand-text underline">Add staff</a></div>
    @else
        <form method="POST" action="{{ route('school.attendance.staff.save') }}" class="card">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div class="card-header flex-wrap gap-3">
                <p class="text-sm text-muted">{{ $records->isEmpty() ? 'Not saved yet for this day.' : 'Saved. Change anything and save again.' }}</p>
                <button type="button" class="btn btn-secondary btn-sm" data-set-all="present">All present</button>
            </div>
            <ul class="divide-y divide-line">
                @foreach ($staff as $m)
                    @php $r = $records[$m->id] ?? null; $current = $r->status ?? (isset($onLeave[$m->id]) ? 'leave' : 'present'); @endphp
                    <li class="grid items-center gap-3 px-5 py-3 md:grid-cols-[1fr_auto_auto]">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">{{ $m->name }} @if (isset($onLeave[$m->id]))<span class="badge badge-info ml-1">On leave</span>@endif</p>
                            <p class="truncate text-xs text-muted">{{ $m->designation ?? $m->employee_no }}</p>
                        </div>
                        <fieldset class="flex flex-wrap gap-1">
                            <legend class="sr-only">Attendance of {{ $m->name }}</legend>
                            @foreach (\App\Http\Controllers\School\Daily\StaffAttendanceController::STATUSES as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[{{ $m->id }}]" value="{{ $value }}" class="peer sr-only" @checked($current === $value)>
                                    <span class="inline-flex min-w-9 items-center justify-center rounded-md border border-line px-2.5 py-1.5 text-xs font-semibold text-muted peer-checked:border-transparent peer-focus-visible:ring-2 peer-focus-visible:ring-brand
                                        {{ ['present' => 'peer-checked:bg-success peer-checked:text-white', 'absent' => 'peer-checked:bg-danger peer-checked:text-white', 'late' => 'peer-checked:bg-warning peer-checked:text-white', 'leave' => 'peer-checked:bg-info peer-checked:text-white', 'half_day' => 'peer-checked:bg-steel peer-checked:text-white'][$value] }}" title="{{ $label }}">{{ \App\Models\StudentAttendance::SHORT[$value] }}<span class="sr-only"> {{ $label }}</span></span>
                                </label>
                            @endforeach
                        </fieldset>
                        <div class="flex gap-2">
                            <input type="time" name="check_in[{{ $m->id }}]" value="{{ $r?->check_in ? substr($r->check_in, 0, 5) : '' }}" class="input w-28 py-1.5 text-sm" aria-label="Time in">
                            <input type="time" name="check_out[{{ $m->id }}]" value="{{ $r?->check_out ? substr($r->check_out, 0, 5) : '' }}" class="input w-28 py-1.5 text-sm" aria-label="Time out">
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save staff attendance</button></div>
        </form>
    @endif
</x-layouts.school>
