<x-layouts.school :heading="$paper->subject?->name.' · '.$section->label()" :subheading="$exam->name.' · out of '.(float) $paper->max_marks.' · pass '.(float) $paper->pass_marks">
    <x-slot:actions><a href="{{ route('school.exams.show', $exam) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a></x-slot:actions>

    @error('marks')<div class="alert alert-danger mb-4"><x-icon name="alert" class="mt-px" /><p>{{ $message }}</p></div>@enderror
    @if ($locked)<div class="alert alert-info mb-4"><x-icon name="lock" class="mt-px" /><p>Marks are locked.</p></div>@endif

    @if ($students->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No students in this section.</div>
    @else
        <form method="POST" action="{{ route('school.exams.marks.update', [$exam, $paper, $section]) }}" class="card">
            @csrf
            @method('PUT')
            <ul class="divide-y divide-line">
                @foreach ($students as $s)
                    @php $m = $marks[$s->id] ?? null; $value = old('marks.'.$s->id, $m?->marks !== null ? (float) $m->marks : null); @endphp
                    <li class="grid items-center gap-2 px-5 py-2.5 sm:grid-cols-[1fr_7rem_auto_12rem]">
                        <p class="min-w-0 truncate text-sm"><span class="font-medium text-ink">{{ $s->roll_no ? $s->roll_no.'. ' : '' }}{{ $s->name }}</span></p>
                        <input type="number" name="marks[{{ $s->id }}]" value="{{ $value }}" min="0" max="{{ (float) $paper->max_marks }}" step="0.25" inputmode="decimal" class="input py-1.5 text-sm tabular {{ $value !== null && (float) $value > (float) $paper->max_marks ? 'input-error' : '' }}" aria-label="Marks of {{ $s->name }}" @disabled($locked)>
                        <label class="flex items-center gap-1.5 text-xs text-body"><input type="checkbox" name="absent[{{ $s->id }}]" value="1" class="checkbox" @checked(old('absent.'.$s->id, $m?->is_absent)) @disabled($locked)> Absent</label>
                        <input type="text" name="remarks[{{ $s->id }}]" value="{{ old('remarks.'.$s->id, $m?->remarks) }}" maxlength="150" placeholder="Remark" class="input py-1.5 text-sm" aria-label="Remark for {{ $s->name }}" @disabled($locked)>
                    </li>
                @endforeach
            </ul>
            @unless ($locked)<div class="sticky bottom-0 flex justify-end border-t border-line bg-white px-5 py-4"><button class="btn btn-primary">Save marks</button></div>@endunless
        </form>
    @endif
</x-layouts.school>
