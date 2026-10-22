@props(['series', 'money' => false, 'tone' => 'brand', 'height' => 'h-48', 'compact' => false])
{{-- Simple accessible bar chart: one bar per month, value shown on hover/focus and in the table for screen readers --}}
@php
    $max = max(1, max(array_column($series, 'value')));
    $fmt = fn ($v) => $money ? money($v) : number_format($v);
    $bar = $tone === 'info' ? 'from-info/70 to-info' : 'from-brand-light to-brand';
    $idle = $tone === 'info' ? 'from-chip to-line-strong group-hover:from-info/40 group-hover:to-info/70' : 'from-chip to-line-strong group-hover:from-brand/40 group-hover:to-brand/70';
@endphp
<div {{ $attributes }}>
    <div class="flex {{ $height }} items-end gap-1.5 sm:gap-2" aria-hidden="true">
        @foreach ($series as $point)
            @php
                $pct = $point['value'] > 0 ? max(3, round($point['value'] / $max * 100)) : 0;
                // Keep the tooltip inside the chart: left-aligned at the start, right-aligned at the end.
                $tip = $loop->index < count($series) / 3 ? 'left-0' : ($loop->index >= count($series) * 2 / 3 ? 'right-0' : 'left-1/2 -translate-x-1/2');
            @endphp
            <div class="group relative flex h-full flex-1 flex-col justify-end">
                <span class="pointer-events-none absolute -top-1 {{ $tip }} z-10 -translate-y-full rounded-md bg-ink px-2 py-1 text-[11px] font-semibold whitespace-nowrap text-white opacity-0 shadow-pop transition group-hover:opacity-100">{{ $point['label'] }}: {{ $fmt($point['value']) }}</span>
                <span class="animate-grow block w-full origin-bottom rounded-t-md bg-gradient-to-t {{ $loop->last ? $bar : $idle }} transition-colors" style="height: {{ $pct }}%; animation-delay: {{ $loop->index * 40 }}ms"></span>
            </div>
        @endforeach
    </div>
    <div class="mt-2 flex gap-1.5 sm:gap-2" aria-hidden="true">
        @foreach ($series as $point)
            <span class="flex-1 truncate text-center text-[10px] text-muted sm:text-xs">{{ $compact ? mb_substr($point['label'], 0, 1) : $point['label'] }}</span>
        @endforeach
    </div>
    <table class="sr-only">
        @foreach ($series as $point)<tr><th>{{ $point['key'] }}</th><td>{{ $fmt($point['value']) }}</td></tr>@endforeach
    </table>
</div>
