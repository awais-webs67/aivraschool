@props(['dark' => false, 'tagline' => false, 'mark' => false])
{{-- Skooline logo: orange tile with an "S" drawn as one line ending in a dot, plus the wordmark --}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-2.5']) }}>
    <x-logo-mark class="size-9 shrink-0" />
    @unless ($mark)
        <span class="flex flex-col">
            <span class="font-display text-[1.4rem] leading-none {{ $dark ? 'text-white' : 'text-ink' }}">Skoo<span class="text-brand">line</span></span>
            @if ($tagline)
                <span class="mt-1 text-[10px] font-semibold tracking-[.14em] uppercase {{ $dark ? 'text-white/50' : 'text-muted' }}">by AivraSol</span>
            @endif
        </span>
    @endunless
</span>
