<?php

namespace App\Support;

/**
 * Applies the SMTP settings saved from the Super Admin panel (kept in .env) to the
 * running app, so a change works on the next request even when config is cached.
 */
class MailSettings
{
    public const KEYS = ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'];

    public static function apply(): void
    {
        // Tests use the in-memory mailer, whatever the developer's .env says.
        if (app()->runningUnitTests()) {
            return;
        }

        if (EnvFile::get('MAIL_MAILER') !== 'smtp' || ! EnvFile::has('MAIL_HOST')) {
            return;
        }

        $encryption = EnvFile::get('MAIL_ENCRYPTION', 'tls');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => EnvFile::get('MAIL_HOST'),
            'mail.mailers.smtp.port' => (int) EnvFile::get('MAIL_PORT', '587'),
            'mail.mailers.smtp.username' => EnvFile::get('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => EnvFile::get('MAIL_PASSWORD'),
            // "ssl" means implicit TLS (usually port 465); "tls" upgrades a plain connection (587).
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => EnvFile::get('MAIL_FROM_ADDRESS', config('mail.from.address')),
            'mail.from.name' => EnvFile::get('MAIL_FROM_NAME', 'Skooline'),
        ]);
    }

    public static function configured(): bool
    {
        return EnvFile::get('MAIL_MAILER') === 'smtp' && EnvFile::has('MAIL_HOST');
    }

    /** Plain-language meaning of an SMTP error. */
    public static function explain(string $message): string
    {
        $m = strtolower($message);

        return match (true) {
            str_contains($m, '535') || str_contains($m, 'authentication') || str_contains($m, 'username and password') => 'The username or password was rejected. For Gmail use an App Password (Google Account → Security → App passwords), not your normal password.',
            str_contains($m, 'connection refused') => 'The server refused the connection. Check the host and port (587 for TLS, 465 for SSL). Some hosts block outgoing SMTP; use your hosting\'s own mail server.',
            str_contains($m, 'timed out') || str_contains($m, 'timeout') => 'No answer from the mail server. The port may be blocked by your network or hosting, or the host name is wrong.',
            str_contains($m, 'getaddrinfo') || str_contains($m, 'name or service not known') || str_contains($m, 'no such host') => 'The mail server name could not be found. Check the host (e.g. smtp.gmail.com, mail.yourdomain.com).',
            str_contains($m, 'ssl') || str_contains($m, 'tls') || str_contains($m, 'certificate') || str_contains($m, 'crypto') => 'Secure connection failed. Try the other encryption setting (TLS with port 587, or SSL with port 465).',
            str_contains($m, '550') || str_contains($m, '553') || str_contains($m, 'sender') => 'The server rejected the sender. The "From" address must be an address this mail account is allowed to send as.',
            default => 'The mail server returned an error. Check every field, then try again.',
        };
    }
}
