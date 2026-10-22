<x-layouts.marketing :title="$title" :description="$title.' for Skooline, the school management system by AivraSol.'">
    <section class="section">
        <div class="container-x max-w-3xl px-4 sm:px-6">
            <p class="eyebrow">Legal</p>
            <h1 class="heading-lg mt-3">{{ $title }}</h1>
            <article class="legal-prose mt-8">{!! $html !!}</article>
            <p class="mt-10 border-t border-line pt-6 text-sm text-muted">
                See also: <a href="{{ route($page === 'privacy' ? 'terms' : 'privacy') }}" class="font-semibold text-brand-text underline underline-offset-2">{{ $page === 'privacy' ? 'Terms of Service' : 'Privacy Policy' }}</a>
            </p>
        </div>
    </section>
</x-layouts.marketing>
