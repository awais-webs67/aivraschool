<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\Setting;
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

        $faqs = [
            ['Do we need to install anything?', 'No. Skooline runs in the browser on computers, tablets and phones. Your school gets its own web address to sign in.'],
            ['Does each school get its own link?', 'Yes. Every school gets a private portal at its own address with its name and logo on the login page.'],
            ['Is our data separate from other schools?', 'Yes. Every record belongs to one school only, and other schools can never access your data.'],
            ['Which currencies and payment plans are available?', 'Fees can be managed in your own currency. Plans are available monthly, quarterly, yearly, lifetime or per student, and we can make a custom plan for your school.'],
            ['Can you import our existing data?', 'Yes. Our team helps import students, staff and fee balances from Excel during setup.'],
            ['Is there a free trial?', 'Yes. Every plan starts with a free trial so your staff can try Skooline before you pay.'],
        ];

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
            'schema' => $schema,
            'appHost' => preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')),
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
