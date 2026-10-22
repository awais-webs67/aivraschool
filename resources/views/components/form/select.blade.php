@props(['name', 'label' => null, 'options' => [], 'value' => null, 'hint' => null, 'required' => false, 'placeholder' => null])
@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-brand" aria-hidden="true">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($error ? ' input-error' : '')]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($error)
        <p class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
