<x-layouts.school heading="Academic sessions" subheading="A session is one school year. Attendance, fees and results are recorded against the current session.">
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Sessions</h2></div>
            @if ($sessions->isEmpty())
                <p class="card-body text-sm text-muted">No sessions yet. Add your current school year.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sessions as $session)
                        <li class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-2 font-semibold text-ink">
                                        {{ $session->name }}
                                        @if ($session->is_current)<span class="badge badge-success">Current</span>@endif
                                    </p>
                                    <p class="tabular text-sm text-muted">{{ $session->starts_on->format('d M Y') }} – {{ $session->ends_on->format('d M Y') }}</p>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @unless ($session->is_current)
                                        <form method="POST" action="{{ route('school.setup.sessions.current', $session) }}">
                                            @csrf
                                            <button class="btn btn-secondary btn-sm">Make current</button>
                                        </form>
                                    @endunless
                                    <button type="button" class="btn btn-ghost btn-sm" data-toggle="#edit-session-{{ $session->id }}"><x-icon name="pencil" size="4" /> Edit</button>
                                    @unless ($session->is_current)
                                        <form method="POST" action="{{ route('school.setup.sessions.destroy', $session) }}" data-confirm="Delete session {{ $session->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                            <form id="edit-session-{{ $session->id }}" hidden method="POST" action="{{ route('school.setup.sessions.update', $session) }}" class="mt-4 grid gap-3 rounded border border-line bg-canvas p-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                                @csrf
                                @method('PUT')
                                <x-form.input name="name" label="Name" :value="$session->name" required :id="'s-name-'.$session->id" />
                                <x-form.input name="starts_on" type="date" label="Starts" :value="$session->starts_on->toDateString()" required :id="'s-start-'.$session->id" />
                                <x-form.input name="ends_on" type="date" label="Ends" :value="$session->ends_on->toDateString()" required :id="'s-end-'.$session->id" />
                                <button class="btn btn-primary">Save</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <form method="POST" action="{{ route('school.setup.sessions.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Add session</h2></div>
            <div class="card-body space-y-4">
                <x-form.input name="name" label="Name" :value="$suggestion['name']" required hint="For example 2027-28" />
                <x-form.input name="starts_on" type="date" label="Starts on" :value="$suggestion['starts_on']" required />
                <x-form.input name="ends_on" type="date" label="Ends on" :value="$suggestion['ends_on']" required />
                <label class="flex items-center gap-2 text-sm text-body">
                    <input type="checkbox" name="is_current" value="1" class="checkbox"> Make this the current session
                </label>
                <button class="btn btn-primary w-full"><x-icon name="plus" size="4" /> Add session</button>
            </div>
        </form>
    </div>
</x-layouts.school>
