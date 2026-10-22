@props(['name', 'label', 'value' => null, 'hint' => null, 'placeholder' => null, 'field' => null, 'id' => null])
{{-- A saved secret (API key, password): shown masked, revealed only with the PIN, replaced by typing a new one --}}
@php
    // $name is the .env key (used by "Show"); $field is the form field name when it differs.
    $field ??= $name;
    $id ??= $field;
    $error = $errors->first($field);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}</label>
    @if ($value)
        <div class="mb-2 flex flex-wrap items-center gap-2 rounded-md border border-line bg-canvas px-3 py-2">
            <span class="badge badge-success shrink-0">Saved</span>
            <code class="min-w-0 flex-1 truncate font-mono text-xs text-ink" data-secret-value="{{ $name }}">{{ \App\Support\EnvFile::mask($value) }}</code>
            <button type="button" class="btn btn-ghost btn-sm shrink-0" data-reveal-secret="{{ $name }}" data-url="{{ route('admin.settings.reveal') }}"><x-icon name="eye" size="4" /> Show</button>
        </div>
    @endif
    <input id="{{ $id }}" name="{{ $field }}" type="password" autocomplete="new-password" spellcheck="false"
        placeholder="{{ $value ? 'Leave blank to keep the saved value' : ($placeholder ?? '') }}"
        {{ $attributes->except('class')->merge(['class' => 'input font-mono'.($error ? ' input-error' : '')]) }}>
    @if ($error)
        <p class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
