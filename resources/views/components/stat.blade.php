@props(['label', 'value', 'icon' => 'chart', 'tone' => 'brand', 'hint' => null])
@php
    $tones = [
        'brand' => 'bg-brand-tint text-brand-text',
        'success' => 'bg-success-tint text-success',
        'info' => 'bg-info-tint text-info',
        'warning' => 'bg-warning-tint text-warning',
        'danger' => 'bg-danger-tint text-danger',
        'neutral' => 'bg-chip text-steel',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card flex items-start gap-4 p-5']) }}>
    <span class="grid size-11 shrink-0 place-items-center rounded-md {{ $tones[$tone] ?? $tones['brand'] }}">
        <x-icon :name="$icon" />
    </span>
    <div class="min-w-0">
        <p class="text-sm text-muted">{{ $label }}</p>
        <p class="font-display tabular mt-1 text-xl leading-tight break-words text-ink sm:text-2xl">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
        @endif
    </div>
</div>
