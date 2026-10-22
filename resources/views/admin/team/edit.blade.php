<x-layouts.admin heading="Edit team member" :subheading="$member->email">
    <x-slot:actions>
        <a href="{{ route('admin.team.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('admin.team.update', $member) }}" class="card max-w-2xl">
        @csrf
        @method('PUT')
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Full name" :value="$member->name" required />
            <x-form.input name="email" type="email" label="Email (username)" :value="$member->email" required />
            <x-form.select name="role" label="Role" required :value="$member->role" :options="\App\Models\PlatformUser::ROLES" />
            <div class="flex items-end">
                <label class="flex items-center gap-2 pb-2.5 text-sm text-ink">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $member->is_active))> Can sign in (active)
                </label>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <a href="{{ route('admin.team.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-layouts.admin>
