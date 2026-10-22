<x-layouts.school heading="Class teachers" subheading="The teacher in charge of each section: attendance, diary and the class report.">
    <x-slot:actions>
        <a href="{{ route('school.staff.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Staff</a>
    </x-slot:actions>

    @if ($sections->isEmpty())
        <div class="card px-4 py-14 text-center">
            <p class="font-semibold text-ink">No classes yet</p>
            <p class="mt-1 text-sm text-muted">Add classes and sections first.</p>
            <a href="{{ route('school.setup.classes') }}" class="btn btn-primary mt-5">Add classes</a>
        </div>
    @elseif ($teachers->isEmpty())
        <div class="card px-4 py-14 text-center">
            <p class="font-semibold text-ink">No staff yet</p>
            <p class="mt-1 text-sm text-muted">Add your teachers first, then pick a class teacher for each section.</p>
            <a href="{{ route('school.staff.create') }}" class="btn btn-primary mt-5">Add staff</a>
        </div>
    @else
        <form method="POST" action="{{ route('school.staff.class-teachers.save') }}" class="card max-w-3xl">
            @csrf
            @method('PUT')
            <ul class="divide-y divide-line">
                @foreach ($sections as $section)
                    <li class="grid items-center gap-2 px-5 py-3 sm:grid-cols-[10rem_1fr]">
                        <label for="teacher-{{ $section->id }}" class="font-semibold text-ink">{{ $section->schoolClass?->name }} – {{ $section->name }}</label>
                        <select id="teacher-{{ $section->id }}" name="teacher[{{ $section->id }}]" class="input">
                            <option value="">No class teacher</option>
                            @foreach ($teachers as $id => $label)<option value="{{ $id }}" @selected((int) $section->class_teacher_id === (int) $id)>{{ $label }}</option>@endforeach
                        </select>
                    </li>
                @endforeach
            </ul>
            <div class="sticky bottom-0 flex justify-end border-t border-line bg-white px-5 py-4"><button class="btn btn-primary">Save class teachers</button></div>
        </form>
    @endif
</x-layouts.school>
