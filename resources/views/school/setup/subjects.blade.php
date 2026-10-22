<x-layouts.school heading="Subjects" subheading="The subjects your school teaches, and which classes study each one.">
    <x-slot:actions>
        <form method="POST" action="{{ route('school.setup.subjects.standard') }}">
            @csrf
            <button class="btn btn-secondary"><x-icon name="bolt" size="4" /> Add common subjects</button>
        </form>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Subjects</h2>
                <span class="text-sm text-muted">{{ $subjects->count() }} subjects</span>
            </div>
            @if ($subjects->isEmpty())
                <div class="flex flex-col items-center px-4 py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="book-open" size="7" /></span>
                    <p class="mt-4 font-semibold text-ink">No subjects yet</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Add subjects one by one, or use <span class="font-medium text-ink">Add common subjects</span> (English, Urdu, Mathematics…).</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($subjects as $subject)
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-ink">
                                        {{ $subject->name }}
                                        @if ($subject->code)<span class="ml-1 font-mono text-xs font-normal text-muted">{{ $subject->code }}</span>@endif
                                        <span class="badge badge-neutral ml-1">{{ \App\Models\Subject::TYPES[$subject->type] ?? $subject->type }}</span>
                                    </p>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $subject->classes->isEmpty() ? 'Not assigned to any class yet' : $subject->classes->pluck('name')->join(', ') }}
                                    </p>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-secondary btn-sm" data-toggle="#edit-subject-{{ $subject->id }}"><x-icon name="pencil" size="4" /> Edit</button>
                                    <form method="POST" action="{{ route('school.setup.subjects.destroy', $subject) }}" data-confirm="Delete {{ $subject->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                                    </form>
                                </div>
                            </div>
                            <form id="edit-subject-{{ $subject->id }}" hidden method="POST" action="{{ route('school.setup.subjects.update', $subject) }}" class="mt-4 space-y-4 rounded border border-line bg-canvas p-4">
                                @csrf
                                @method('PUT')
                                @include('school.setup.partials.subject-fields', ['subject' => $subject, 'prefix' => 'sub-'.$subject->id])
                                <button class="btn btn-primary">Save</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <form method="POST" action="{{ route('school.setup.subjects.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Add subject</h2></div>
            <div class="card-body space-y-4">
                @include('school.setup.partials.subject-fields', ['subject' => null, 'prefix' => 'new'])
                <button class="btn btn-primary w-full"><x-icon name="plus" size="4" /> Add subject</button>
            </div>
        </form>
    </div>
</x-layouts.school>
