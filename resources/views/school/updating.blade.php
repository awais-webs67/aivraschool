<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => 'Updating'])
</head>
<body class="grid min-h-full place-items-center px-4 py-16">
    <main class="w-full max-w-md text-center">
        <x-logo-mark class="mx-auto size-14" />
        <h1 class="font-display mt-6 text-3xl text-ink">We're updating Skooline</h1>
        <p class="mt-3 text-body">New features are being installed. Please try again in a few minutes.</p>
        <button type="button" onclick="location.reload()" class="btn btn-primary mt-8"><x-icon name="refresh" size="4" /> Try again</button>
    </main>
</body>
</html>
