<?php

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Jobs\Middleware\InitializeTenantContext;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register AttendanceCalculationService as singleton
        // This service is used by scheduled commands, controllers, and models
        $this->app->singleton(\App\Services\AttendanceCalculationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\LeaveRequest::observe(\App\Observers\LeaveRequestObserver::class);

        // Register custom FCM notification channel
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('fcm', function ($app) {
                return new FcmChannel(
                    $app->make(\App\Services\FcmNotificationService::class)
                );
            });
        });

        // Register queue tenant awareness
        $this->registerQueueTenantAwareness();

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                      (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : ''),
                ];
            }

            return [];
        });

        /**
         * Register Custom Migration Paths
         */
        $this->loadMigrationsFrom([
            database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'tenant',
        ]);
    }

    /**
     * Register queue tenant awareness for multi-tenant system
     */
    protected function registerQueueTenantAwareness(): void
    {
        // Add tenant_id to all queued jobs automatically
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            // Only add tenant context if in SaaS mode
            if (! isSaaSMode()) {
                return [];
            }

            $tenant = tenant();

            if ($tenant) {
                return [
                    'tenant_id' => $tenant->id,
                ];
            }

            return [];
        });

        // Apply tenant context middleware to all jobs
        Queue::before(function (\Illuminate\Queue\Events\JobProcessing $event) {
            // Skip if not in SaaS mode
            if (! isSaaSMode()) {
                return;
            }

            $middleware = new InitializeTenantContext;
            $middleware->handle($event->job, function ($job) {
                // Job continues processing
            });
        });
    }
}
