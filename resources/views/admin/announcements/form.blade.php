@php $editing = $announcement->exists; @endphp
<x-layouts.admin :heading="$editing ? 'Edit announcement' : 'New announcement'" subheading="Schools see this on their dashboard while it is active.">
    <x-slot:actions>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="card max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="title" label="Title" :value="$announcement->title" required class="sm:col-span-2" />
            <x-form.textarea name="body" label="Message" :value="$announcement->body" rows="5" required class="sm:col-span-2" />
            <x-form.select name="tone" label="Type" :value="$announcement->tone" :options="\App\Models\Announcement::TONES" required />
            <x-form.select name="audience" label="Send to" :value="$announcement->audience" :options="\App\Models\Announcement::AUDIENCES" required />
            <div data-show-when="audience=plan" class="sm:col-span-2">
                <x-form.select name="plan_id" label="Plan" :value="$announcement->plan_id" :options="$plans" placeholder="Choose a plan" />
            </div>
            <div data-show-when="audience=school" class="sm:col-span-2">
                <x-form.select name="school_id" label="School" :value="$announcement->school_id" :options="$schools" placeholder="Choose a school" />
            </div>
            <x-form.input name="starts_on" type="date" label="Show from" :value="$announcement->starts_on?->toDateString()" hint="Blank = straight away." />
            <x-form.input name="ends_on" type="date" label="Show until" :value="$announcement->ends_on?->toDateString()" hint="Blank = until you switch it off." />
            <label class="flex items-center gap-2 text-sm text-ink sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $announcement->is_active))> Active
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Publish' }}</button>
        </div>
    </form>
</x-layouts.admin>
