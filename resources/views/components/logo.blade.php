@props(['dark' => false, 'tagline' => false])
{{-- Skooline wordmark: Bebas Neue lettering with the orange "line" --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="relative grid size-9 shrink-0 place-items-center rounded-md {{ $dark ? 'bg-white text-ink' : 'bg-ink text-white' }}">
        <span class="font-display text-2xl leading-none">S</span>
        <span class="absolute right-1.5 bottom-1.5 left-1.5 h-[3px] rounded-full bg-brand"></span>
    </span>
    <span class="flex flex-col leading-none">
        <span class="font-display text-[1.65rem] leading-none {{ $dark ? 'text-white' : 'text-ink' }}">Skoo<span class="text-brand">line</span></span>
        @if ($tagline)
            <span class="mt-0.5 text-[10px] font-medium tracking-wider uppercase {{ $dark ? 'text-white/50' : 'text-muted' }}">by AivraSol</span>
        @endif
    </span>
</span>
