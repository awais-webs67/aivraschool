<x-layouts.school heading="Classes & sections" subheading="Set up the classes your school teaches and the sections in each.">
    <x-slot:actions>
        <form method="POST" action="{{ route('school.setup.classes.standard') }}">
            @csrf
            <button class="btn btn-secondary"><x-icon name="bolt" size="4" /> Add Playgroup – Class 10</button>
        </form>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Classes</h2>
                <span class="text-sm text-muted">{{ $classes->count() }} classes · {{ $classes->sum(fn ($c) => $c->sections->count()) }} sections</span>
            </div>
            @if ($classes->isEmpty())
                <div class="flex flex-col items-center px-4 py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="layers" size="7" /></span>
                    <p class="mt-4 font-semibold text-ink">No classes yet</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Add classes one by one, or use <span class="font-medium text-ink">Add Playgroup – Class 10</span> to create the standard set in one click.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($classes as $class)
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-ink">{{ $class->name }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        @foreach ($class->sections as $section)
                                            <span class="inline-flex items-center gap-1 rounded-sm border border-line bg-canvas py-0.5 pr-1 pl-2 text-xs font-medium text-ink">
                                                {{ $section->name }}@if ($section->capacity)<span class="text-muted">· {{ $section->capacity }}</span>@endif
                                                @if ($class->sections->count() > 1)
                                                    <form method="POST" action="{{ route('school.setup.sections.destroy', $section) }}" data-confirm="Delete section {{ $class->name }}-{{ $section->name }}?" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="rounded p-0.5 text-muted hover:bg-danger-tint hover:text-danger" aria-label="Delete section {{ $section->name }}"><x-icon name="x" size="3" /></button>
                                                    </form>
                                                @endif
                                            </span>
                                        @endforeach
                                        <span class="text-xs text-muted">· {{ $class->subjects_count }} {{ \Illuminate\Support\Str::plural('subject', $class->subjects_count) }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-secondary btn-sm" data-toggle="#add-section-{{ $class->id }}"><x-icon name="plus" size="4" /> Section</button>
                                    <button type="button" class="btn btn-ghost btn-sm" data-toggle="#edit-class-{{ $class->id }}"><x-icon name="pencil" size="4" /><span class="sr-only">Edit</span></button>
                                    <form method="POST" action="{{ route('school.setup.classes.destroy', $class) }}" data-confirm="Delete {{ $class->name }} and all its sections?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                                    </form>
                                </div>
                            </div>
                            <form id="add-section-{{ $class->id }}" hidden method="POST" action="{{ route('school.setup.sections.store', $class) }}" class="mt-4 grid gap-3 rounded border border-line bg-canvas p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                @csrf
                                <x-form.input name="name" label="Section name" placeholder="B" required :id="'sec-name-'.$class->id" />
                                <x-form.input name="capacity" type="number" min="1" label="Capacity (optional)" :id="'sec-cap-'.$class->id" />
                                <button class="btn btn-primary">Add section</button>
                            </form>
                            <form id="edit-class-{{ $class->id }}" hidden method="POST" action="{{ route('school.setup.classes.update', $class) }}" class="mt-4 grid gap-3 rounded border border-line bg-canvas p-4 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
                                @csrf
                                @method('PUT')
                                <x-form.input name="name" label="Class name" :value="$class->name" required :id="'cls-name-'.$class->id" />
                                <x-form.input name="sort_order" type="number" min="0" label="Order" :value="$class->sort_order" :id="'cls-order-'.$class->id" />
                                <button class="btn btn-primary">Save</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <form method="POST" action="{{ route('school.setup.classes.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Add class</h2></div>
            <div class="card-body space-y-4">
                <x-form.input name="name" label="Class name" required placeholder="e.g. Class 6 or O Level 1" />
                <x-form.input name="sort_order" type="number" min="0" label="Order (optional)" hint="Lower numbers are listed first. Blank = after the last class." />
                <p class="text-xs text-muted">Section A is created automatically. Add more sections from the list.</p>
                <button class="btn btn-primary w-full"><x-icon name="plus" size="4" /> Add class</button>
            </div>
        </form>
    </div>
</x-layouts.school>
