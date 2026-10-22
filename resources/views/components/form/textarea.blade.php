@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'rows' => 3])
@php $error = $errors->first($name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }}</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->except('class')->merge(['class' => 'input'.($error ? ' input-error' : '')]) }}>{{ old($name, $value) }}</textarea>
    @if ($error)
        <p class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
