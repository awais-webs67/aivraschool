@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $error = $errors->first($name);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-brand" aria-hidden="true">*</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($error ? ' input-error' : '')]) }}>
    @if ($error)
        <p id="{{ $id }}-error" class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
