<?php

namespace App\Support;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Website texts the Super Admin can edit (Settings → Website). Anything left blank uses the default below.
 */
class WebsiteContent
{
    public const KEYS = ['hero_title', 'hero_highlight', 'hero_subtitle', 'hero_points', 'contact_email', 'contact_phone', 'contact_whatsapp', 'contact_address', 'faqs', 'testimonials', 'privacy', 'terms'];

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'hero_title' => 'Run your entire school from',
            'hero_highlight' => 'one place.',
            'hero_subtitle' => 'Admissions, attendance, fees, exams, staff and parent communication in one secure system. Every school gets its own private portal that works on any device.',
            'hero_points' => ['Free trial on every plan', 'No installation needed', 'Works on phone & laptop'],
            'contact_email' => config('skooline.sales_email'),
            'contact_phone' => null,
            'contact_whatsapp' => null,
            'contact_address' => null,
            'faqs' => [
                ['q' => 'Do we need to install anything?', 'a' => 'No. Skooline runs in the browser on computers, tablets and phones. Your school gets its own web address to sign in.'],
                ['q' => 'Does each school get its own link?', 'a' => 'Yes. Every school gets a private portal at its own address with its name and logo on the login page.'],
                ['q' => 'Is our data separate from other schools?', 'a' => 'Yes. Every record belongs to one school only, and other schools can never access your data.'],
                ['q' => 'Which currencies and payment plans are available?', 'a' => 'Fees can be managed in your own currency. Plans are available monthly, quarterly, yearly, lifetime or per student, and we can make a custom plan for your school.'],
                ['q' => 'Can you import our existing data?', 'a' => 'Yes. Our team helps import students, staff and fee balances from Excel during setup.'],
                ['q' => 'Is there a free trial?', 'a' => 'Yes. Every plan starts with a free trial so your staff can try Skooline before you pay.'],
            ],
            'testimonials' => [],
            'privacy' => self::template('privacy'),
            'terms' => self::template('terms'),
        ];
    }

    /** One value: the saved one, or the default when blank. */
    public static function get(string $key): mixed
    {
        $saved = (array) Setting::get('website_content', []);
        $value = $saved[$key] ?? null;

        return blank($value) ? self::defaults()[$key] ?? null : $value;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => self::get($k)])->all();
    }

    /** Built-in policy text with the company name, email and date filled in. */
    public static function template(string $page): string
    {
        $company = SettingsController::values();
        $file = resource_path("legal/{$page}.md");

        return strtr((string) file_get_contents($file), [
            '{company}' => $company['company_name'],
            '{email}' => $company['company_email'] ?: config('skooline.sales_email'),
            '{date}' => date('j F Y', (int) filemtime($file)),
        ]);
    }

    /** Markdown → safe HTML (raw HTML in the text is escaped). */
    public static function html(string $markdown): string
    {
        return Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
