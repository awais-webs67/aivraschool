@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'rows' => 3])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $key = str_replace(['[', ']'], ['.', ''], $name); // "faqs[0][a]" → "faqs.0.a" for old input and errors
    $error = $errors->first($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($error ? ' input-error' : '')]) }}>{{ old($key, $value) }}</textarea>
    @if ($error)
        <p class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
