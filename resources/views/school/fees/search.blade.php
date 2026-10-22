<x-layouts.school heading="Fees" subheading="Find the student who is paying.">
    @include('school.fees._nav')
    <form method="GET" class="card mb-4 flex flex-wrap gap-2 p-4" role="search">
        <input type="search" name="q" value="{{ $q }}" class="input min-w-0 flex-1" placeholder="Name, admission no. or father's phone" aria-label="Find student" autofocus>
        <button class="btn btn-primary">Search</button>
    </form>
    @if ($q !== '')
        <div class="card">
            <ul class="divide-y divide-line">
                @forelse ($results as $s)
                    <li><a href="{{ route('school.fees.student', $s) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-canvas">
                        <div class="min-w-0 flex-1"><p class="truncate font-medium text-ink">{{ $s->name }}</p><p class="truncate text-xs text-muted">{{ $s->admission_no }} · {{ $s->classLabel() }} · {{ $s->father_name }}</p></div>
                        <x-icon name="chevron-right" size="4" class="text-muted" />
                    </a></li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">No student found.</li>
                @endforelse
            </ul>
        </div>
    @endif
</x-layouts.school>
