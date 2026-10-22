<x-layouts.school :heading="'Result analysis · '.$exam->name" subheading="How each class and subject did.">
    <x-slot:actions><a href="{{ route('school.exams.show', $exam) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a></x-slot:actions>

    @if (! $overall)
        <div class="card px-4 py-14 text-center text-sm text-muted">No marks entered yet.</div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
            <x-stat label="Students with results" :value="number_format($overall['students'])" icon="users" tone="neutral" />
            <x-stat label="School average" :value="$overall['average'].'%'" icon="chart" tone="brand" />
            <x-stat label="Pass rate" :value="$overall['passRate'].'%'" icon="check-circle" :tone="$overall['passRate'] >= 80 ? 'success' : 'warning'" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">By class</h2></div>
                <ul class="divide-y divide-line">
                    @foreach ($bySection as $row)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3 text-sm"><a href="{{ route('school.exams.results', [$exam, $row['section']]) }}" class="font-medium text-ink hover:text-brand-text">{{ $row['section']->label() }}</a><span class="text-muted">avg {{ $row['average'] }}% · pass {{ $row['passRate'] }}%</span></div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full {{ $row['average'] >= 60 ? 'bg-success' : ($row['average'] >= 40 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, $row['average']) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">By subject (weakest first)</h2></div>
                <ul class="divide-y divide-line">
                    @foreach ($subjects as $row)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium text-ink">{{ $row['name'] }}</span><span class="text-muted">avg {{ $row['average'] }}% · pass {{ $row['passRate'] }}%</span></div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full {{ $row['average'] >= 60 ? 'bg-success' : ($row['average'] >= 40 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, $row['average']) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <section class="card mt-6">
            <div class="card-header"><h2 class="card-title">Top 10 students</h2></div>
            <ol class="divide-y divide-line">
                @foreach ($top as $i => $r)
                    <li class="flex items-center gap-4 px-5 py-2.5 text-sm">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full {{ $i < 3 ? 'bg-brand text-white' : 'bg-chip text-ink' }} text-xs font-bold">{{ $i + 1 }}</span>
                        <span class="min-w-0 flex-1 truncate"><span class="font-medium text-ink">{{ $r['student']->name }}</span> <span class="text-muted">· {{ $r['section']->label() }}</span></span>
                        <span class="font-semibold tabular">{{ $r['percent'] }}%</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</x-layouts.school>
