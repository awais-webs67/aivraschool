@php
    $siteName = 'Skooline';
    $fullTitle = filled($title ?? null) ? $title.' | '.$siteName : $siteName.' | School Management System by AivraSol';
    $metaDescription = $description ?? 'Skooline is an all-in-one school management system: admissions, attendance, fees, exams, staff, timetable and parent communication, with a private portal for every school.';
    $indexable = $index ?? false;
    $canonical = $canonical ?? url()->current();
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
@if ($indexable)
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Skooline school management system">
    <meta property="og:locale" content="en_US">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
@else
    <meta name="robots" content="noindex, nofollow">
@endif
<meta name="theme-color" content="#11161B">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('images/icon-192.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
{{-- Start loading a page when the pointer rests on its link, so the click feels instant (Chrome/Edge). --}}
<script type="speculationrules">{"prefetch":[{"where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":"/*export*"}},{"not":{"href_matches":"/*/invoice"}},{"not":{"selector_matches":"[data-no-progress], [download], [target=_blank]"}}]},"eagerness":"moderate"}]}</script>
<script>document.documentElement.classList.add('js')</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
