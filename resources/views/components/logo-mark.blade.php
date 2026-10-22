@php $id = 'sk'.substr(md5(uniqid('', true)), 0, 6); @endphp
<svg viewBox="0 0 48 48" aria-hidden="true" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#FF7A45" />
            <stop offset="1" stop-color="#F0441C" />
        </linearGradient>
    </defs>
    <rect width="48" height="48" rx="13" fill="url(#{{ $id }})" />
    <path d="M33 14.5H20.5a5.75 5.75 0 0 0 0 11.5h7a5.75 5.75 0 0 1 0 11.5H15" fill="none" stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="35" cy="14.5" r="3.2" fill="#fff" />
</svg>
