<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\RegistrationService;
use App\Support\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->active()->where('show_on_website', true)->ordered()->get();
        $currency = Setting::currency();

        $content = WebsiteContent::all();
        $faqs = array_map(fn ($f) => [$f['q'], $f['a']], $content['faqs']);

        // Structured data for search engines (organization, product with prices, FAQ).
        $schema = ['@context' => 'https://schema.org', '@graph' => [
            ['@type' => 'Organization', '@id' => url('/#org'), 'name' => 'AivraSol', 'url' => config('skooline.company_url'), 'email' => config('skooline.sales_email')],
            ['@type' => 'WebSite', 'name' => 'Skooline', 'url' => url('/'), 'inLanguage' => 'en', 'publisher' => ['@id' => url('/#org')]],
            array_filter([
                '@type' => 'SoftwareApplication',
                'name' => 'Skooline',
                'applicationCategory' => 'BusinessApplication',
                'applicationSubCategory' => 'School management system',
                'operatingSystem' => 'Web browser',
                'url' => url('/'),
                'image' => asset('images/og-image.png'),
                'description' => 'All-in-one school management system for admissions, attendance, fees, exams, staff and parent communication.',
                'publisher' => ['@id' => url('/#org')],
                'offers' => $plans->map(fn (Plan $plan) => [
                    '@type' => 'Offer',
                    'name' => $plan->name,
                    'price' => (string) (float) ($plan->isPerStudent() ? $plan->price_per_student : $plan->price),
                    'priceCurrency' => $currency,
                ])->values()->all() ?: null,
            ]),
            ['@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
            ], $faqs)],
        ]];

        return view('home', [
            'plans' => $plans,
            'modules' => config('skooline.modules'),
            'currencies' => Setting::websiteCurrencies(),
            'faqs' => $faqs,
            'content' => $content,
            'schema' => $schema,
            'appHost' => preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')),
        ]);
    }

    /** Privacy policy and terms of service (Settings → Website). */
    public function legal(string $page): View
    {
        return view('legal', [
            'page' => $page,
            'title' => $page === 'privacy' ? 'Privacy Policy' : 'Terms of Service',
            'html' => WebsiteContent::html(WebsiteContent::get($page)),
        ]);
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /$', 'Allow: /build/', 'Allow: /images/', 'Disallow: /admin', 'Disallow: /*/login', 'Disallow: /*/dashboard', 'Disallow: /*/setup', '', 'Sitemap: '.url('sitemap.xml')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $updated = Plan::query()->max('updated_at') ?? now();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .'  <url><loc>'.e(url('/')).'</loc><lastmod>'.Carbon::parse($updated)->toDateString().'</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>'."\n"
            .collect(array_filter(['register' => RegistrationService::enabled(), 'privacy' => true, 'terms' => true]))
                ->keys()->map(fn ($name) => '  <url><loc>'.e(route($name)).'</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>'."\n")->join('')
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function requestDemo(Request $request): RedirectResponse
    {
        $key = 'demo-request:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->to(route('home').'#demo')
                ->withErrors(['phone' => 'Too many requests. Please try again later.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'school_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:80'],
            'students' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        RateLimiter::hit($key, 3600);

        DemoRequest::create($data + ['ip_address' => $request->ip()]);

        return redirect()->to(route('home').'#demo')
            ->with('demo_success', 'Thank you! Our team will contact you within one working day.');
    }
}
