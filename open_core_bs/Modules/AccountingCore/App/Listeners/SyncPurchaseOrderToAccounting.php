<?php

namespace Modules\AccountingCore\App\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\AccountingCore\App\Services\AccountingSyncService;
use Modules\SystemCore\App\Events\PurchaseOrderPaymentStatusChanged;

class SyncPurchaseOrderToAccounting
{
    protected AccountingSyncService $syncService;

    /**
     * Create the event listener.
     */
    public function __construct(AccountingSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderPaymentStatusChanged $event): void
    {
        try {
            // Only sync when payment status changes to paid or partial
            if (in_array($event->newStatus, ['paid', 'partial'])) {
                $this->syncService->syncPurchaseOrder($event->purchaseOrder);
            }

            // If payment status changes from paid/partial to pending, remove the transaction
            if (in_array($event->oldStatus, ['paid', 'partial']) && $event->newStatus === 'pending') {
                $this->syncService->unsyncOrder($event->purchaseOrder);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync purchase order to accounting', [
                'order_id' => $event->purchaseOrder->id,
                'order_number' => $event->purchaseOrder->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
