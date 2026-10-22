<x-layouts.school heading="Import students" subheading="Add your whole student list from Excel in one go.">
    <x-slot:actions>
        <a href="{{ route('school.students.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Students</a>
    </x-slot:actions>

    @if ($problems = session('import_errors'))
        <div class="alert alert-danger mb-6" role="alert">
            <x-icon name="alert" class="mt-px" />
            <div class="min-w-0">
                <p class="font-semibold">Nothing was imported. Please fix these rows:</p>
                <ul class="mt-2 list-disc space-y-0.5 pl-5">@foreach ($problems as $p)<li class="break-words">{{ $p }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">3 easy steps</h2></div>
            <ol class="card-body space-y-5 text-sm text-body">
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-white">1</span>
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">Download the template</p>
                        <p class="mt-0.5">Open it in Excel or Google Sheets and put one student on each row. Keep the first row (the column names) as it is.</p>
                        <a href="{{ route('school.students.import.template') }}" data-no-progress class="btn btn-secondary btn-sm mt-3"><x-icon name="download" size="4" /> Template (.csv)</a>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-white">2</span>
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">Save as CSV</p>
                        <p class="mt-0.5">In Excel: File → Save As → <strong>CSV (Comma delimited)</strong>. Only <strong>name</strong> and <strong>class</strong> are required. Leave admission_no empty to number students automatically.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-white">3</span>
                    <form method="POST" action="{{ route('school.students.import.store') }}" enctype="multipart/form-data" class="min-w-0 flex-1">
                        @csrf
                        <p class="font-semibold text-ink">Upload the file</p>
                        <p class="mt-0.5">Every row is checked first. If one row is wrong, nothing is saved and you see what to fix.</p>
                        <div class="mt-3 flex flex-wrap items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <input type="file" name="file" accept=".csv,text/csv" required class="input" aria-label="CSV file">
                                @error('file')<p class="error">{{ $message }}</p>@enderror
                            </div>
                            <button class="btn btn-primary"><x-icon name="upload" size="4" /> Import</button>
                        </div>
                    </form>
                </li>
            </ol>
        </section>

        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Your class names</h2></div>
            <div class="card-body text-sm">
                <p class="text-muted">The <strong>class</strong> column must match one of these. A new section letter is created for you.</p>
                <ul class="mt-3 space-y-1">
                    @forelse ($classes as $class)
                        <li class="flex justify-between gap-2"><span class="font-medium text-ink">{{ $class->name }}</span><span class="text-muted">{{ $class->sections->pluck('name')->join(', ') }}</span></li>
                    @empty
                        <li class="text-muted">No classes yet. <a href="{{ route('school.setup.classes') }}" class="font-semibold text-brand-text underline">Add classes</a></li>
                    @endforelse
                </ul>
                <p class="mt-4 text-xs text-muted">Columns: {{ implode(', ', $columns) }}. Dates like 2016-04-12 or 12/04/2016.</p>
            </div>
        </section>
    </div>
</x-layouts.school>
