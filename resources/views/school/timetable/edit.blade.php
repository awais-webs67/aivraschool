<x-layouts.school :heading="'Edit timetable · '.$section->label()" subheading="Pick the subject and teacher for each period. A teacher can't be in two classes at the same time.">
    <x-slot:actions>
        <a href="{{ route('school.timetable.index', ['section' => $section->id]) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    @if ($errors->has('cell'))
        <div class="alert alert-danger mb-4"><x-icon name="alert" class="mt-px" /><div><p class="font-semibold">Nothing was saved. Fix these clashes:</p><ul class="mt-1 list-disc pl-5">@foreach ($errors->get('cell') as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
    @endif

    @if ($periods->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">Add periods first. <a href="{{ route('school.timetable.periods') }}" class="font-semibold text-brand-text underline">Set up periods</a></div>
    @else
        <form method="POST" action="{{ route('school.timetable.update', $section) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @foreach ($days as $d => $dayName)
                <details class="card group" @if ($loop->first || old('cell')) open @endif>
                    <summary class="card-header cursor-pointer list-none"><h2 class="card-title">{{ $dayName }}</h2><x-icon name="chevron-right" size="4" class="transition group-open:rotate-90" /></summary>
                    <div class="divide-y divide-line">
                        @foreach ($periods as $p)
                            @php $e = $entries[$d.'-'.$p->id] ?? null; $base = "cell[$d][{$p->id}]"; @endphp
                            <div class="grid items-center gap-2 px-5 py-2.5 sm:grid-cols-[8rem_1fr_1fr_6rem]">
                                <p class="text-sm"><span class="font-semibold text-ink">{{ $p->name }}</span> <span class="text-xs text-muted tabular">{{ $p->timeRange() }}</span></p>
                                <select name="{{ $base }}[subject_id]" class="input py-1.5 text-sm" aria-label="{{ $dayName }} {{ $p->name }} subject">
                                    <option value="">No subject</option>
                                    @foreach ($subjects as $id => $name)<option value="{{ $id }}" @selected((int) old("cell.$d.{$p->id}.subject_id", $e?->subject_id) === (int) $id)>{{ $name }}</option>@endforeach
                                </select>
                                <select name="{{ $base }}[staff_id]" class="input py-1.5 text-sm" aria-label="{{ $dayName }} {{ $p->name }} teacher">
                                    <option value="">No teacher</option>
                                    @foreach ($teachers as $id => $name)<option value="{{ $id }}" @selected((int) old("cell.$d.{$p->id}.staff_id", $e?->staff_id) === (int) $id)>{{ $name }}</option>@endforeach
                                </select>
                                <input type="text" name="{{ $base }}[room]" value="{{ old("cell.$d.{$p->id}.room", $e?->room) }}" placeholder="Room" maxlength="30" class="input py-1.5 text-sm" aria-label="{{ $dayName }} {{ $p->name }} room">
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach
            <div class="sticky bottom-0 flex justify-end border-t border-line bg-canvas/90 py-4 backdrop-blur"><button class="btn btn-primary">Save timetable</button></div>
        </form>
    @endif
</x-layouts.school>
