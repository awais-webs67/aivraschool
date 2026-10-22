<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use App\Support\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Settings → Website: hero text, contact details, FAQ, testimonials, privacy policy and terms. */
class WebsiteController extends Controller
{
    /** Empty rows shown under the saved FAQ / testimonial rows for adding new ones. */
    private const SPARE_ROWS = 3;

    public function edit(): View
    {
        $content = WebsiteContent::all();

        return view('admin.settings.website', [
            'c' => $content,
            'faqs' => array_merge($content['faqs'], array_fill(0, self::SPARE_ROWS, ['q' => '', 'a' => ''])),
            'testimonials' => array_merge($content['testimonials'], array_fill(0, self::SPARE_ROWS, ['name' => '', 'role' => '', 'school' => '', 'quote' => ''])),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_highlight' => ['nullable', 'string', 'max:60'],
            'hero_subtitle' => ['nullable', 'string', 'max:400'],
            'hero_points' => ['nullable', 'string', 'max:400'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'faqs' => ['array', 'max:30'],
            'faqs.*.q' => ['nullable', 'string', 'max:200'],
            'faqs.*.a' => ['nullable', 'string', 'max:1000'],
            'testimonials' => ['array', 'max:30'],
            'testimonials.*.name' => ['nullable', 'string', 'max:80'],
            'testimonials.*.role' => ['nullable', 'string', 'max:80'],
            'testimonials.*.school' => ['nullable', 'string', 'max:120'],
            'testimonials.*.quote' => ['nullable', 'string', 'max:600'],
            'privacy' => ['nullable', 'string', 'max:50000'],
            'terms' => ['nullable', 'string', 'max:50000'],
        ]);

        $content = [
            'hero_title' => $data['hero_title'] ?? null,
            'hero_highlight' => $data['hero_highlight'] ?? null,
            'hero_subtitle' => $data['hero_subtitle'] ?? null,
            'hero_points' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['hero_points'] ?? ''))))) ?: null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_whatsapp' => $data['contact_whatsapp'] ?? null,
            'contact_address' => $data['contact_address'] ?? null,
            // Rows with an empty question or name are dropped. No FAQ rows at all = default questions.
            'faqs' => array_values(array_filter($data['faqs'] ?? [], fn ($f) => filled($f['q'] ?? null) && filled($f['a'] ?? null))) ?: null,
            'testimonials' => array_values(array_filter($data['testimonials'] ?? [], fn ($t) => filled($t['name'] ?? null) && filled($t['quote'] ?? null))),
            // Unchanged default text is not stored, so it keeps following the company name and email.
            'privacy' => $this->unlessDefault($data['privacy'] ?? null, 'privacy'),
            'terms' => $this->unlessDefault($data['terms'] ?? null, 'terms'),
        ];

        Setting::put(['website_content' => $content]);
        Activity::log('settings.website', 'Updated website content');

        return back()->with('success', 'Website content saved.');
    }

    protected function unlessDefault(?string $text, string $page): ?string
    {
        $text = trim(str_replace("\r\n", "\n", (string) $text));

        return $text === '' || $text === trim(WebsiteContent::template($page)) ? null : $text;
    }
}
