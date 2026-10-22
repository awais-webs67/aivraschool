@props(['school', 'size' => 'md'])
{{-- School logo if uploaded, otherwise its first letter --}}
@php $box = $size === 'lg' ? 'size-14 text-3xl' : ($size === 'sm' ? 'size-8 text-lg' : 'size-11 text-2xl'); @endphp
@if ($school->logo_path)
    <img src="{{ route('school.logo', ['school' => $school->slug, 'v' => $school->updated_at?->timestamp]) }}" alt="{{ $school->name }} logo"
        {{ $attributes->merge(['class' => "$box shrink-0 rounded-md bg-white object-contain p-0.5"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$box font-display grid shrink-0 place-items-center rounded-md bg-ink text-white"]) }}>{{ strtoupper(mb_substr($school->name, 0, 1)) }}</span>
@endif
