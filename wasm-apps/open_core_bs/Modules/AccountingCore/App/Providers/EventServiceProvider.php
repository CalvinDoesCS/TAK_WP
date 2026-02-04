<?php

namespace Modules\AccountingCore\App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\SystemCore\App\Events\SalesOrderPaymentStatusChanged::class => [
            \Modules\AccountingCore\App\Listeners\SyncSalesOrderToAccounting::class,
        ],
        \Modules\SystemCore\App\Events\PurchaseOrderPaymentStatusChanged::class => [
            \Modules\AccountingCore\App\Listeners\SyncPurchaseOrderToAccounting::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
