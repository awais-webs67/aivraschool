<x-layouts.school heading="Dashboard" :subheading="'Welcome, '.auth('school')->user()->name.'.'">
    @php $remaining = collect($setup)->where('done', false)->count(); @endphp

    @foreach ($announcements as $a)
        <div class="alert alert-{{ $a->tone }} mb-4" role="status">
            <x-icon :name="$a->tone === 'danger' || $a->tone === 'warning' ? 'alert' : 'bolt'" class="mt-px" />
            <div class="min-w-0">
                <p class="font-semibold">{{ $a->title }}</p>
                <p class="mt-0.5 whitespace-pre-line opacity-90">{{ $a->body }}</p>
                <p class="mt-1 text-xs opacity-70">From AivraSol · {{ $a->created_at->format('d M Y') }}</p>
            </div>
        </div>
    @endforeach

    @if ($canSetup && $remaining > 0)
        <section class="card mb-6 border-brand/40">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Set up your school</h2>
                    <p class="text-sm text-muted">{{ count($setup) - $remaining }} of {{ count($setup) }} steps done</p>
                </div>
                <div class="h-2 w-40 overflow-hidden rounded-full bg-chip" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($setup) }}" aria-valuenow="{{ count($setup) - $remaining }}">
                    <div class="h-full rounded-full bg-brand" style="width: {{ round((count($setup) - $remaining) / count($setup) * 100) }}%"></div>
                </div>
            </div>
            <ol class="divide-y divide-line">
                @foreach ($setup as $i => $step)
                    <li>
                        <a href="{{ route($step['route']) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-canvas">
                            @if ($step['done'])
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-success text-white"><x-icon name="check" size="4" /></span>
                            @else
                                <span class="grid size-8 shrink-0 place-items-center rounded-full border-2 border-line-strong text-sm font-semibold text-muted">{{ $i + 1 }}</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium {{ $step['done'] ? 'text-muted line-through' : 'text-ink' }}">{{ $step['label'] }}</span>
                                <span class="block text-xs text-muted">{{ $step['hint'] }}</span>
                            </span>
                            <x-icon name="chevron-right" size="4" class="text-muted" />
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Current session" :value="$session?->name ?? 'Not set'" icon="calendar" tone="brand"
            :hint="$session ? $session->starts_on->format('M Y').' – '.$session->ends_on->format('M Y') : null" />
        <x-stat label="Classes" :value="number_format($counts['classes'])" icon="layers" tone="info" :hint="$counts['sections'].' sections'" />
        <x-stat label="Subjects" :value="number_format($counts['subjects'])" icon="book-open" tone="success" />
        <x-stat label="Plan" :value="$school->plan?->name ?? '—'" icon="shield" tone="neutral"
            :hint="$school->accessEndsAt() ? $school->status->label().' until '.$school->accessEndsAt()->format('d M Y') : $school->status->label()" />
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Your modules</h2>
            <span class="text-xs text-muted">New modules switch on here as they are released.</span>
        </div>
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($modules as $module)
                <div class="flex items-start gap-3 rounded border p-4 {{ $module['enabled'] ? 'border-line bg-white' : 'border-dashed border-line bg-canvas' }}">
                    <span class="grid size-10 shrink-0 place-items-center rounded-md {{ $module['enabled'] ? 'bg-brand-tint text-brand-text' : 'bg-chip text-muted' }}"><x-icon :name="$module['icon']" /></span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold {{ $module['enabled'] ? 'text-ink' : 'text-muted' }}">{{ $module['label'] }}</p>
                        <p class="text-xs text-muted">{{ $module['enabled'] ? $module['description'] : 'Not in your plan. Contact AivraSol to upgrade.' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.school>
