<x-layouts.school :heading="'Attendance · '.$section->label()" :subheading="$date->format('l, d M Y')">
    <x-slot:actions>
        <a href="{{ route('school.attendance.index', ['date' => $date->toDateString()]) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> All classes</a>
        <a href="{{ route('school.attendance.register', [$section, 'month' => $date->format('Y-m')]) }}" class="btn btn-secondary"><x-icon name="calendar" size="4" /> Monthly register</a>
    </x-slot:actions>

    @if ($students->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No students in this section yet.</div>
    @else
        @unless ($canEdit)
            <div class="alert alert-warning mb-4"><x-icon name="lock" class="mt-px" /><p>This day is locked. Only the principal can change it now.</p></div>
        @endunless

        <form method="POST" action="{{ route('school.attendance.save', $section) }}" class="card">
            @csrf
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div class="card-header flex-wrap gap-3">
                <p class="text-sm text-muted">{{ $records->isEmpty() ? 'Everyone starts as present. Tap only the absent ones.' : 'Saved earlier. Change anything and save again.' }}</p>
                @if ($canEdit)
                    <div class="flex gap-1">
                        <button type="button" class="btn btn-secondary btn-sm" data-set-all="present">All present</button>
                        <button type="button" class="btn btn-ghost btn-sm" data-set-all="absent">All absent</button>
                    </div>
                @endif
            </div>
            <ul class="divide-y divide-line">
                @foreach ($students as $s)
                    @php $current = $records[$s->id]->status ?? 'present'; @endphp
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3">
                        <div class="min-w-[10rem] flex-1">
                            <p class="font-medium text-ink">{{ $s->roll_no ? $s->roll_no.'. ' : '' }}{{ $s->name }}</p>
                            <p class="text-xs text-muted">{{ $s->admission_no }}</p>
                        </div>
                        <fieldset class="flex flex-wrap gap-1" @disabled(! $canEdit)>
                            <legend class="sr-only">Attendance of {{ $s->name }}</legend>
                            @foreach (\App\Models\StudentAttendance::STATUSES as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[{{ $s->id }}]" value="{{ $value }}" class="peer sr-only" @checked($current === $value)>
                                    <span class="inline-flex min-w-9 items-center justify-center rounded-md border border-line px-2.5 py-1.5 text-xs font-semibold text-muted transition peer-checked:border-transparent peer-focus-visible:ring-2 peer-focus-visible:ring-brand
                                        {{ ['present' => 'peer-checked:bg-success peer-checked:text-white', 'absent' => 'peer-checked:bg-danger peer-checked:text-white', 'late' => 'peer-checked:bg-warning peer-checked:text-white', 'leave' => 'peer-checked:bg-info peer-checked:text-white', 'half_day' => 'peer-checked:bg-steel peer-checked:text-white'][$value] }}"
                                        title="{{ $label }}">{{ \App\Models\StudentAttendance::SHORT[$value] }}<span class="sr-only"> {{ $label }}</span></span>
                                </label>
                            @endforeach
                        </fieldset>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4">
                <p class="text-xs text-muted">P present · A absent · L late · Lv leave · H half day</p>
                @if ($canEdit)<button class="btn btn-primary">Save attendance</button>@endif
            </div>
        </form>
    @endif
</x-layouts.school>
