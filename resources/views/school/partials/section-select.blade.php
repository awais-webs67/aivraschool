{{--
    Class & section picker grouped by class.
    $name, $id (optional), $label (optional), $value (selected section id), $placeholder, $required, $groups (Section::grouped())
--}}
@php
    $fieldId = $id ?? $name;
    $selected = (string) old($name, $value ?? '');
    $error = $errors->first($name);
@endphp
<div class="{{ $class ?? '' }}">
    @if ($label ?? null)<label for="{{ $fieldId }}" class="label">{{ $label }} @if ($required ?? false)<span class="text-brand" aria-hidden="true">*</span>@endif</label>@endif
    <select id="{{ $fieldId }}" name="{{ $name }}" class="input {{ $error ? 'input-error' : '' }}" @required($required ?? false) @if (! ($label ?? null)) aria-label="{{ $placeholder ?? 'Class' }}" @endif>
        <option value="">{{ $placeholder ?? 'Choose class & section' }}</option>
        @foreach ($groups as $className => $sections)
            <optgroup label="{{ $className }}">
                @foreach ($sections as $sid => $sectionLabel)<option value="{{ $sid }}" @selected($selected === (string) $sid)>{{ $sectionLabel }}</option>@endforeach
            </optgroup>
        @endforeach
    </select>
    @if ($error)<p class="error">{{ $error }}</p>@elseif ($hint ?? null)<p class="hint">{{ $hint }}</p>@endif
</div>
