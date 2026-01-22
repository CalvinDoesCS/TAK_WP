<?php

namespace App\Providers;

use App\Models\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            // Load mail configuration from database settings
            $settings = Settings::first();

            if ($settings && $settings->mail_host) {
                Config::set('mail.default', $settings->mail_driver ?? 'smtp');
                Config::set('mail.mailers.smtp.transport', $settings->mail_driver ?? 'smtp');
                Config::set('mail.mailers.smtp.host', $settings->mail_host);
                Config::set('mail.mailers.smtp.port', $settings->mail_port ?? 587);
                Config::set('mail.mailers.smtp.username', $settings->mail_username);
                Config::set('mail.mailers.smtp.password', $settings->mail_password);
                Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption ?? 'tls');
                Config::set('mail.from.address', $settings->mail_from_address ?? $settings->company_email ?? 'noreply@example.com');
                Config::set('mail.from.name', $settings->mail_from_name ?? $settings->company_name ?? config('app.name'));
            }
        } catch (\Exception $e) {
            // If database is not available or settings table doesn't exist,
            // fall back to .env configuration
            // This allows the application to work during installation/migration
        }
    }
}
