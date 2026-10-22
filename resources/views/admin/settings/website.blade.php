<x-layouts.admin heading="Settings" subheading="Texts on your public website. Leave a field blank to use the built-in text.">
    @include('admin.settings.tabs')

    <form method="POST" action="{{ route('admin.settings.website.update') }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Top of the home page</h2>
                    <p class="mt-0.5 text-sm text-muted">The big headline visitors see first.</p>
                </div>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="globe" size="4" /> View site</a>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="hero_title" label="Headline" :value="$c['hero_title']" maxlength="120" />
                <x-form.input name="hero_highlight" label="Highlighted end of headline" :value="$c['hero_highlight']" maxlength="60" hint="Shown in the brand colour." />
                <x-form.textarea name="hero_subtitle" label="Text under the headline" rows="3" :value="$c['hero_subtitle']" maxlength="400" class="sm:col-span-2" />
                <x-form.textarea name="hero_points" label="Tick points (one per line)" rows="3" :value="implode(PHP_EOL, $c['hero_points'])" class="sm:col-span-2" />
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Contact details</h2>
                    <p class="mt-0.5 text-sm text-muted">Shown in the website footer.</p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="contact_email" type="email" label="Email" :value="$c['contact_email']" />
                <x-form.input name="contact_phone" label="Phone" :value="$c['contact_phone']" placeholder="+1 555 010 0000" />
                <x-form.input name="contact_whatsapp" label="WhatsApp number" :value="$c['contact_whatsapp']" placeholder="With country code" />
                <x-form.input name="contact_address" label="Address" :value="$c['contact_address']" />
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">FAQ</h2>
                    <p class="mt-0.5 text-sm text-muted">Clear both boxes of a row to remove it. Save to get more empty rows.</p>
                </div>
            </div>
            <div class="card-body space-y-4">
                @foreach ($faqs as $i => $f)
                    <div class="grid gap-3 rounded-lg border border-line p-4 sm:grid-cols-[1fr_1.5fr]">
                        <x-form.input name="faqs[{{ $i }}][q]" :id="'faq-q-'.$i" label="Question {{ $i + 1 }}" :value="$f['q']" maxlength="200" />
                        <x-form.textarea name="faqs[{{ $i }}][a]" :id="'faq-a-'.$i" label="Answer" rows="2" :value="$f['a']" maxlength="1000" />
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Testimonials</h2>
                    <p class="mt-0.5 text-sm text-muted">Quotes from real schools. The section appears on the home page once you add one.</p>
                </div>
            </div>
            <div class="card-body space-y-4">
                @foreach ($testimonials as $i => $t)
                    <div class="grid gap-3 rounded-lg border border-line p-4 sm:grid-cols-3">
                        <x-form.input name="testimonials[{{ $i }}][name]" :id="'t-name-'.$i" label="Name" :value="$t['name']" maxlength="80" />
                        <x-form.input name="testimonials[{{ $i }}][role]" :id="'t-role-'.$i" label="Role" :value="$t['role'] ?? ''" placeholder="Principal" maxlength="80" />
                        <x-form.input name="testimonials[{{ $i }}][school]" :id="'t-school-'.$i" label="School" :value="$t['school'] ?? ''" maxlength="120" />
                        <x-form.textarea name="testimonials[{{ $i }}][quote]" :id="'t-quote-'.$i" label="Quote" rows="2" :value="$t['quote']" maxlength="600" class="sm:col-span-3" />
                    </div>
                @endforeach
            </div>
        </section>

        @foreach (['privacy' => 'Privacy Policy', 'terms' => 'Terms of Service'] as $key => $label)
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ $label }}</h2>
                        <p class="mt-0.5 text-sm text-muted">Markdown: <code>## Heading</code>, <code>- list item</code>, <code>**bold**</code>. Clear the box to go back to the built-in text.</p>
                    </div>
                    <a href="{{ route($key) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">View page</a>
                </div>
                <div class="card-body">
                    <x-form.textarea :name="$key" :label="$label" rows="14" :value="$c[$key]" class="font-mono text-sm" />
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 flex justify-end border-t border-line bg-canvas/90 py-4 backdrop-blur">
            <button class="btn btn-primary">Save website</button>
        </div>
    </form>
</x-layouts.admin>
