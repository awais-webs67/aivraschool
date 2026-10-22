@props(['name', 'label', 'hint' => null, 'current' => null])
{{-- Image upload with a live preview; $current is the URL of the photo already saved --}}
@php $error = $errors->first($name); @endphp
<div {{ $attributes->only('class') }}>
    <span class="label">{{ $label }}</span>
    <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-dashed border-line-strong bg-canvas/60 p-3 transition hover:border-brand/50 {{ $error ? 'border-danger' : '' }}">
        <span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-md bg-white text-muted ring-1 ring-line">
            <img data-photo-preview="{{ $name }}" src="{{ $current ?? '' }}" alt="" class="size-full object-cover {{ $current ? '' : 'hidden' }}">
            <x-icon name="plus" data-photo-placeholder="{{ $name }}" class="{{ $current ? 'hidden' : '' }}" />
        </span>
        <span class="min-w-0 text-sm">
            <span class="font-semibold text-ink">{{ $current ? 'Change photo' : 'Choose a photo' }}</span>
            <span class="block text-xs text-muted" data-photo-name="{{ $name }}">{{ $hint }}</span>
        </span>
        <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-input="{{ $name }}">
    </label>
    @if ($error)<p class="error">{{ $error }}</p>@endif
</div>
