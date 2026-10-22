@php $selected = collect(old('class_ids', $subject?->classes->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all(); @endphp
<div class="grid gap-4 sm:grid-cols-2">
    <x-form.input name="name" label="Subject name" :value="$subject?->name" required :id="$prefix.'-name'" class="sm:col-span-2" />
    <x-form.input name="code" label="Code (optional)" :value="$subject?->code" :id="$prefix.'-code'" placeholder="ENG" />
    <x-form.select name="type" label="Type" :options="\App\Models\Subject::TYPES" :value="$subject?->type ?? 'theory'" :id="$prefix.'-type'" />
</div>
<fieldset>
    <legend class="label">Taught in classes</legend>
    @if ($classes->isEmpty())
        <p class="text-sm text-muted">Add classes first, then come back to assign subjects.</p>
    @else
        <div class="grid max-h-56 grid-cols-2 gap-1.5 overflow-y-auto rounded border border-line bg-white p-2">
            @foreach ($classes as $class)
                <label class="flex items-center gap-2 rounded px-2 py-1 text-sm text-ink hover:bg-canvas">
                    <input type="checkbox" name="class_ids[]" value="{{ $class->id }}" class="checkbox" @checked(in_array($class->id, $selected, true))>
                    {{ $class->name }}
                </label>
            @endforeach
        </div>
    @endif
</fieldset>
