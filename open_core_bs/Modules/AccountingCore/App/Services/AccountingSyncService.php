<?php

namespace Modules\AccountingCore\App\Services;

use App\Services\Settings\ModuleSettingsService;
use Illuminate\Database\Eloquent\Model;
use Modules\AccountingCore\App\Models\BasicTransaction;
use Modules\AccountingCore\App\Models\BasicTransactionCategory;
use Modules\SystemCore\App\Models\CorePurchaseOrder;
use Modules\SystemCore\App\Models\CoreSalesOrder;
use Modules\SystemCore\App\Services\PostingLogService;

class AccountingSyncService
{
    protected ModuleSettingsService $settingsService;

    protected PostingLogService $postingLogService;

    public function __construct(
        ModuleSettingsService $settingsService,
        PostingLogService $postingLogService
    ) {
        $this->settingsService = $settingsService;
        $this->postingLogService = $postingLogService;
    }

    /**
     * Sync a sales order to accounting as income transaction.
     */
    public function syncSalesOrder(CoreSalesOrder $salesOrder, bool $force = false): ?BasicTransaction
    {
        // Check if auto-sync is enabled
        if (! $force && ! $this->isAutoSyncEnabled('sales_orders')) {
            return null;
        }

        // Only sync if order is completed or paid
        if (! in_array($salesOrder->payment_status, ['paid', 'partial'])) {
            return null;
        }

        try {
            // Check if already synced
            $existingTransaction = BasicTransaction::where('sourceable_type', CoreSalesOrder::class)
                ->where('sourceable_id', $salesOrder->id)
                ->first();

            if ($existingTransaction) {
                // Update existing transaction
                $transaction = $this->updateSalesOrderTransaction($existingTransaction, $salesOrder);

                $this->postingLogService->logSuccess(
                    sourceModule: 'SystemCore',
                    sourceType: 'sales_order',
                    sourceId: $salesOrder->id,
                    targetModule: 'AccountingCore',
                    targetType: 'transaction',
                    targetId: $transaction->id,
                    action: 'update',
                    description: "Sales Order {$salesOrder->order_number} -> Transaction {$transaction->transaction_number}",
                    amount: $salesOrder->total_amount,
                    newValues: $transaction->toArray()
                );

                return $transaction;
            }

            // Get or create Sales category
            $category = $this->getSalesCategory();

            // Calculate transaction amount based on payment status
            $amount = $this->calculateTransactionAmount($salesOrder);

            // Create new transaction
            $transaction = BasicTransaction::create([
                'type' => 'income',
                'amount' => $amount,
                'category_id' => $category->id,
                'description' => sprintf(
                    'Sales Order: %s - Customer: %s',
                    $salesOrder->order_number,
                    $salesOrder->customer?->name ?? 'N/A'
                ),
                'transaction_date' => $salesOrder->order_date,
                'reference_number' => $salesOrder->order_number,
                'payment_method' => $salesOrder->payment_method,
                'sourceable_type' => CoreSalesOrder::class,
                'sourceable_id' => $salesOrder->id,
                'sync_status' => $force ? 'manual' : 'auto_synced',
                'tags' => ['sales', 'automated'],
                'created_by_id' => auth()->id() ?? $salesOrder->user_id,
            ]);

            $this->postingLogService->logSuccess(
                sourceModule: 'SystemCore',
                sourceType: 'sales_order',
                sourceId: $salesOrder->id,
                targetModule: 'AccountingCore',
                targetType: 'transaction',
                targetId: $transaction->id,
                action: 'create',
                description: "Sales Order {$salesOrder->order_number} -> Transaction {$transaction->transaction_number}",
                amount: $salesOrder->total_amount,
                newValues: $transaction->toArray()
            );

            return $transaction;
        } catch (\Exception $e) {
            $this->postingLogService->logFailure(
                sourceModule: 'SystemCore',
                sourceType: 'sales_order',
                sourceId: $salesOrder->id,
                targetModule: 'AccountingCore',
                targetType: 'transaction',
                action: 'create',
                errorMessage: $e->getMessage(),
                description: "Failed to sync Sales Order {$salesOrder->order_number}",
                amount: $salesOrder->total_amount
            );
            throw $e;
        }
    }

    /**
     * Sync a purchase order to accounting as expense transaction.
     */
    public function syncPurchaseOrder(CorePurchaseOrder $purchaseOrder, bool $force = false): ?BasicTransaction
    {
        // Check if auto-sync is enabled
        if (! $force && ! $this->isAutoSyncEnabled('purchase_orders')) {
            return null;
        }

        // Only sync if order is completed or paid
        if (! in_array($purchaseOrder->payment_status, ['paid', 'partial'])) {
            return null;
        }

        try {
            // Check if already synced
            $existingTransaction = BasicTransaction::where('sourceable_type', CorePurchaseOrder::class)
                ->where('sourceable_id', $purchaseOrder->id)
                ->first();

            if ($existingTransaction) {
                // Update existing transaction
                $transaction = $this->updatePurchaseOrderTransaction($existingTransaction, $purchaseOrder);

                $this->postingLogService->logSuccess(
                    sourceModule: 'SystemCore',
                    sourceType: 'purchase_order',
                    sourceId: $purchaseOrder->id,
                    targetModule: 'AccountingCore',
                    targetType: 'transaction',
                    targetId: $transaction->id,
                    action: 'update',
                    description: "Purchase Order {$purchaseOrder->order_number} -> Transaction {$transaction->transaction_number}",
                    amount: $purchaseOrder->total_amount,
                    newValues: $transaction->toArray()
                );

                return $transaction;
            }

            // Get or create Purchases/Inventory category
            $category = $this->getPurchasesCategory();

            // Calculate transaction amount based on payment status
            $amount = $this->calculateTransactionAmount($purchaseOrder);

            // Create new transaction
            $transaction = BasicTransaction::create([
                'type' => 'expense',
                'amount' => $amount,
                'category_id' => $category->id,
                'description' => sprintf(
                    'Purchase Order: %s - Supplier: %s',
                    $purchaseOrder->order_number,
                    $purchaseOrder->supplier?->name ?? 'N/A'
                ),
                'transaction_date' => $purchaseOrder->order_date,
                'reference_number' => $purchaseOrder->order_number,
                'payment_method' => $purchaseOrder->payment_method,
                'sourceable_type' => CorePurchaseOrder::class,
                'sourceable_id' => $purchaseOrder->id,
                'sync_status' => $force ? 'manual' : 'auto_synced',
                'tags' => ['purchases', 'automated'],
                'created_by_id' => auth()->id() ?? $purchaseOrder->user_id,
            ]);

            $this->postingLogService->logSuccess(
                sourceModule: 'SystemCore',
                sourceType: 'purchase_order',
                sourceId: $purchaseOrder->id,
                targetModule: 'AccountingCore',
                targetType: 'transaction',
                targetId: $transaction->id,
                action: 'create',
                description: "Purchase Order {$purchaseOrder->order_number} -> Transaction {$transaction->transaction_number}",
                amount: $purchaseOrder->total_amount,
                newValues: $transaction->toArray()
            );

            return $transaction;
        } catch (\Exception $e) {
            $this->postingLogService->logFailure(
                sourceModule: 'SystemCore',
                sourceType: 'purchase_order',
                sourceId: $purchaseOrder->id,
                targetModule: 'AccountingCore',
                targetType: 'transaction',
                action: 'create',
                errorMessage: $e->getMessage(),
                description: "Failed to sync Purchase Order {$purchaseOrder->order_number}",
                amount: $purchaseOrder->total_amount
            );
            throw $e;
        }
    }

    /**
     * Update existing sales order transaction.
     */
    protected function updateSalesOrderTransaction(BasicTransaction $transaction, CoreSalesOrder $salesOrder): BasicTransaction
    {
        $amount = $this->calculateTransactionAmount($salesOrder);

        $transaction->update([
            'amount' => $amount,
            'description' => sprintf(
                'Sales Order: %s - Customer: %s',
                $salesOrder->order_number,
                $salesOrder->customer?->name ?? 'N/A'
            ),
            'payment_method' => $salesOrder->payment_method,
            'transaction_date' => $salesOrder->order_date,
        ]);

        return $transaction;
    }

    /**
     * Update existing purchase order transaction.
     */
    protected function updatePurchaseOrderTransaction(BasicTransaction $transaction, CorePurchaseOrder $purchaseOrder): BasicTransaction
    {
        $amount = $this->calculateTransactionAmount($purchaseOrder);

        $transaction->update([
            'amount' => $amount,
            'description' => sprintf(
                'Purchase Order: %s - Supplier: %s',
                $purchaseOrder->order_number,
                $purchaseOrder->supplier?->name ?? 'N/A'
            ),
            'payment_method' => $purchaseOrder->payment_method,
            'transaction_date' => $purchaseOrder->order_date,
        ]);

        return $transaction;
    }

    /**
     * Calculate transaction amount based on payment status.
     */
    protected function calculateTransactionAmount(Model $order): float
    {
        // If partially paid, only record the paid amount
        // For now, we'll record the full amount - can be enhanced to track partial payments
        return $order->total_amount;
    }

    /**
     * Get or create Sales category.
     */
    protected function getSalesCategory(): BasicTransactionCategory
    {
        $category = BasicTransactionCategory::where('name', 'Sales')
            ->where('type', 'income')
            ->first();

        if (! $category) {
            $category = BasicTransactionCategory::create([
                'name' => 'Sales',
                'type' => 'income',
                'icon' => 'bx bx-dollar',
                'color' => '#28a745',
                'is_active' => true,
                'created_by_id' => auth()->id(),
            ]);
        }

        return $category;
    }

    /**
     * Get or create Purchases category.
     */
    protected function getPurchasesCategory(): BasicTransactionCategory
    {
        $category = BasicTransactionCategory::where('name', 'Purchases')
            ->where('type', 'expense')
            ->first();

        if (! $category) {
            $category = BasicTransactionCategory::create([
                'name' => 'Purchases',
                'type' => 'expense',
                'icon' => 'bx bx-shopping-bag',
                'color' => '#dc3545',
                'is_active' => true,
                'created_by_id' => auth()->id(),
            ]);
        }

        return $category;
    }

    /**
     * Check if auto-sync is enabled for a specific type.
     */
    protected function isAutoSyncEnabled(string $type): bool
    {
        return (bool) $this->settingsService->get(
            'AccountingCore',
            "auto_sync_{$type}",
            true
        );
    }

    /**
     * Remove accounting transaction for an order.
     */
    public function unsyncOrder(Model $order): bool
    {
        $transaction = BasicTransaction::where('sourceable_type', get_class($order))
            ->where('sourceable_id', $order->id)
            ->first();

        if ($transaction) {
            $transactionId = $transaction->id;
            $transactionNumber = $transaction->transaction_number;
            $sourceType = $order instanceof CoreSalesOrder ? 'sales_order' : 'purchase_order';
            $deleted = $transaction->delete();

            if ($deleted) {
                $this->postingLogService->logSuccess(
                    sourceModule: 'SystemCore',
                    sourceType: $sourceType,
                    sourceId: $order->id,
                    targetModule: 'AccountingCore',
                    targetType: 'transaction',
                    targetId: $transactionId,
                    action: 'delete',
                    description: "Removed transaction {$transactionNumber} for order {$order->order_number}"
                );
            }

            return $deleted;
        }

        return false;
    }

    /**
     * Get transaction for an order.
     */
    public function getTransactionForOrder(Model $order): ?BasicTransaction
    {
        return BasicTransaction::where('sourceable_type', get_class($order))
            ->where('sourceable_id', $order->id)
            ->first();
    }

    /**
     * Check if order is synced to accounting.
     */
    public function isOrderSynced(Model $order): bool
    {
        return BasicTransaction::where('sourceable_type', get_class($order))
            ->where('sourceable_id', $order->id)
            ->exists();
    }

    /**
     * Bulk sync all eligible orders.
     */
    public function bulkSyncOrders(string $orderType): array
    {
        $synced = [];
        $errors = [];

        if ($orderType === 'sales') {
            $orders = CoreSalesOrder::whereIn('payment_status', ['paid', 'partial'])->get();

            foreach ($orders as $order) {
                try {
                    $transaction = $this->syncSalesOrder($order, true);
                    if ($transaction) {
                        $synced[] = $order->order_number;
                    }
                } catch (\Exception $e) {
                    $errors[$order->order_number] = $e->getMessage();
                }
            }
        } elseif ($orderType === 'purchases') {
            $orders = CorePurchaseOrder::whereIn('payment_status', ['paid', 'partial'])->get();

            foreach ($orders as $order) {
                try {
                    $transaction = $this->syncPurchaseOrder($order, true);
                    if ($transaction) {
                        $synced[] = $order->order_number;
                    }
                } catch (\Exception $e) {
                    $errors[$order->order_number] = $e->getMessage();
                }
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($synced),
        ];
    }
}
