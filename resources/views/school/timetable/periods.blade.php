<x-layouts.school heading="Timetable" subheading="The school day: periods, breaks and the days the school is open.">
    @include('school.timetable._nav')
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Periods</h2></div>
            @if ($periods->isEmpty())
                <form method="POST" action="{{ route('school.timetable.periods.standard') }}" class="card-body flex flex-wrap items-end gap-3">
                    @csrf
                    <p class="w-full text-sm text-muted">Start quickly: 8 lessons with a break after the 4th.</p>
                    <x-form.input name="start" type="time" label="School starts" value="08:00" required />
                    <x-form.input name="minutes" type="number" min="20" max="90" label="Lesson minutes" value="40" required class="w-36" />
                    <button class="btn btn-primary">Make periods</button>
                </form>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($periods as $p)
                        <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                            <span class="w-28 font-mono text-xs text-muted tabular">{{ $p->timeRange() }}</span>
                            <span class="min-w-0 flex-1 font-medium {{ $p->is_break ? 'text-muted' : 'text-ink' }}">{{ $p->name }}</span>
                            @if ($p->is_break)<span class="badge badge-neutral">Break</span>@endif
                            <form method="POST" action="{{ route('school.timetable.periods.destroy', $p) }}" data-confirm="Remove {{ $p->name }}? Lessons in it are removed too.">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Remove {{ $p->name }}"><x-icon name="trash" size="4" /></button></form>
                        </li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('school.timetable.periods.store') }}" class="grid gap-3 border-t border-line p-5 sm:grid-cols-[1fr_7rem_7rem_auto_auto] sm:items-end">
                @csrf
                <x-form.input name="name" label="Add period" placeholder="Period 9" required maxlength="40" />
                <x-form.input name="starts_at" type="time" label="From" required />
                <x-form.input name="ends_at" type="time" label="To" required />
                <label class="flex items-center gap-2 pb-2.5 text-sm"><input type="checkbox" name="is_break" value="1" class="checkbox"> Break</label>
                <button class="btn btn-secondary">Add</button>
            </form>
        </section>

        <form method="POST" action="{{ route('school.timetable.days') }}" class="card self-start">
            @csrf
            @method('PUT')
            <div class="card-header"><h2 class="card-title">School days</h2></div>
            <div class="card-body grid grid-cols-2 gap-2">
                @foreach (\App\Models\TimetableEntry::DAYS as $n => $name)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="days[]" value="{{ $n }}" class="checkbox" @checked(in_array($n, $days, true))> {{ $name }}</label>
                @endforeach
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save days</button></div>
        </form>
    </div>
</x-layouts.school>
