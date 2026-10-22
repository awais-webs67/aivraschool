<x-layouts.school :heading="$member->name" :subheading="collect([$member->employee_no, $member->designation, $member->department])->filter()->join(' · ')">
    <x-slot:actions>
        <a href="{{ route('school.staff.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> All staff</a>
        <a href="{{ route('school.staff.edit', $member) }}" class="btn btn-primary"><x-icon name="pencil" size="4" /> Edit</a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card self-start">
            <div class="card-body flex flex-col items-center text-center">
                @include('school.staff._avatar', ['m' => $member, 'size' => 'size-24 text-2xl'])
                <p class="mt-4 text-lg font-semibold break-words text-ink">{{ $member->name }}</p>
                <p class="text-sm text-muted">{{ $member->designation ?? 'No designation' }}</p>
                <div class="mt-3 flex flex-wrap justify-center gap-1">
                    <span class="badge {{ $member->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ $member->status === 'active' ? 'Working here' : 'Left '.($member->left_on?->format('d M Y') ?? '') }}</span>
                    @if ($member->user?->is_active)<span class="badge badge-brand">{{ $member->user->roleLabel() }} login</span>@endif
                </div>
            </div>
            <dl class="divide-y divide-line border-t border-line text-sm">
                @foreach (['Phone' => $member->phone, 'Email' => $member->email, 'Address' => $member->address] as $label => $value)
                    <div class="flex gap-3 px-5 py-3"><dt class="w-20 shrink-0 text-muted">{{ $label }}</dt><dd class="min-w-0 break-words text-ink">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Details</h2></div>
                <dl class="card-body grid gap-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Employee no.' => $member->employee_no,
                        'Department' => $member->department,
                        'Qualification' => $member->qualification,
                        'Joining date' => $member->joining_date?->format('d M Y'),
                        'Monthly salary' => $member->salary !== null ? current_school()->currencyCode().' '.number_format((float) $member->salary, 2) : null,
                        'Father / husband' => $member->father_name,
                        'Gender' => \App\Models\Staff::GENDERS[$member->gender] ?? null,
                        'Date of birth' => $member->date_of_birth?->format('d M Y'),
                        'National ID' => $member->national_id,
                    ] as $label => $value)
                        <div><dt class="text-xs text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if ($member->notes)<p class="border-t border-line px-5 py-4 text-sm whitespace-pre-line text-body">{{ $member->notes }}</p>@endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Class teacher of</h2><a href="{{ route('school.staff.class-teachers') }}" class="btn btn-ghost btn-sm">Change</a></div>
                <div class="card-body">
                    @forelse ($member->classSections as $section)
                        <span class="badge badge-neutral mr-1 mb-1">{{ $section->schoolClass?->name }}-{{ $section->name }}</span>
                    @empty
                        <p class="text-sm text-muted">Not a class teacher.</p>
                    @endforelse
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Portal login</h2></div>
                <div class="card-body text-sm">
                    @if ($member->user?->is_active)
                        <p class="text-body">Signs in as <span class="font-semibold text-ink">{{ $member->user->email }}</span> with the <span class="font-semibold text-ink">{{ $member->user->roleLabel() }}</span> role.</p>
                        <p class="mt-1 text-xs text-muted">Last sign-in: {{ $member->user->last_login_at?->diffForHumans() ?? 'never' }}</p>
                    @else
                        <p class="text-muted">No portal access. <a href="{{ route('school.staff.edit', $member) }}" class="font-semibold text-brand-text underline">Give a login</a></p>
                    @endif
                </div>
            </section>

            @if ($member->user_id !== auth('school')->id())
                <form method="POST" action="{{ route('school.staff.destroy', $member) }}" data-confirm="Delete {{ $member->name }} and their login for good? To keep the record, set the status to 'Left' instead." class="flex justify-end">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger-outline btn-sm"><x-icon name="trash" size="4" /> Delete record</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.school>
