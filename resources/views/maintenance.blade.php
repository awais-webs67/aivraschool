<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => 'Back soon', 'index' => false])
</head>
<body class="grid min-h-full place-items-center bg-canvas px-4 py-10">
    <main class="w-full max-w-md text-center">
        <x-logo tagline class="justify-center" />
        <div class="card mt-8 p-8">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="clock" size="7" /></span>
            <h1 class="font-display mt-5 text-3xl text-ink">We'll be right back</h1>
            <p class="mt-3 text-sm break-words whitespace-pre-line text-body">{{ $message ?: 'Skooline is being updated to serve you better. Please try again in a few minutes. Your data is safe.' }}</p>
        </div>
    </main>
</body>
</html>
