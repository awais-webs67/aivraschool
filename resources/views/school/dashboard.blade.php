<x-layouts.school heading="Dashboard" :subheading="'Welcome, '.auth('school')->user()->name.'.'">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Plan" :value="$school->plan?->name ?? '—'" icon="layers" tone="brand" :hint="$school->plan?->billingLabel()" />
        <x-stat label="Status" :value="$school->status->label()" icon="shield" tone="success"
            :hint="$school->accessEndsAt() ? 'Until '.$school->accessEndsAt()->format('d M Y') : 'No end date'" />
        <x-stat label="Modules enabled" :value="$modules->where('enabled', true)->count().' / '.$modules->count()" icon="cube" tone="info" />
        <x-stat label="User accounts" :value="number_format($userCount)" icon="users" tone="neutral" />
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Your modules</h2>
            <span class="text-xs text-muted">Modules are being rolled out. More features unlock automatically.</span>
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
