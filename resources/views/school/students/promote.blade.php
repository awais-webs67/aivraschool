<x-layouts.school heading="Promote students" subheading="At the end of the year, move a class up, or mark the final class as passed out.">
    <x-slot:actions>
        <a href="{{ route('school.students.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Students</a>
    </x-slot:actions>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        @include('school.partials.section-select', ['name' => 'from', 'label' => 'Students of', 'groups' => $groups, 'value' => $from?->id, 'class' => 'min-w-0 flex-1 sm:max-w-xs'])
        <button class="btn btn-secondary">Show students</button>
    </form>

    @if ($from)
        @if ($students->isEmpty())
            <div class="card px-4 py-14 text-center text-sm text-muted">No students studying in {{ $from->label() }}.</div>
        @else
            <form method="POST" action="{{ route('school.students.promote.store') }}" class="card" data-confirm="Apply this to the ticked students?">
                @csrf
                <input type="hidden" name="from" value="{{ $from->id }}">
                <div class="card-header flex-wrap gap-3">
                    <h2 class="card-title">{{ $from->label() }} · {{ $students->count() }} students</h2>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" class="checkbox" checked data-check-all="students[]"> Tick all</label>
                </div>
                @error('students')<p class="error px-5 pt-3">{{ $message }}</p>@enderror
                <ul class="grid gap-x-6 divide-y divide-line sm:grid-cols-2 sm:divide-y-0">
                    @foreach ($students as $s)
                        <li class="px-5 py-2.5">
                            <label class="flex items-center gap-3 text-sm">
                                <input type="checkbox" name="students[]" value="{{ $s->id }}" class="checkbox" checked>
                                <span class="min-w-0 truncate"><span class="font-medium text-ink">{{ $s->name }}</span> <span class="text-muted">· {{ $s->roll_no ? 'Roll '.$s->roll_no : $s->admission_no }}</span></span>
                            </label>
                        </li>
                    @endforeach
                </ul>
                <div class="grid gap-4 border-t border-line p-5 sm:grid-cols-2">
                    <fieldset class="space-y-2">
                        <legend class="label">What to do</legend>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="action" value="move" class="size-4 accent-brand" @checked(old('action', 'move') === 'move')> Move to another class</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="action" value="graduate" class="size-4 accent-brand" @checked(old('action') === 'graduate')> Mark as passed out (final class)</label>
                    </fieldset>
                    <div data-show-when="action=move">
                        @include('school.partials.section-select', ['name' => 'to', 'label' => 'Move to', 'groups' => $groups, 'value' => $suggested?->id])
                    </div>
                </div>
                <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Apply</button></div>
            </form>
        @endif
    @endif
</x-layouts.school>
