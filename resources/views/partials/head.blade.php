<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#191F24">
<title>{{ isset($title) ? $title.' · ' : '' }}Skooline by AivraSol</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=bebas-neue:400|inter:400,500,600,700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
