<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for WASM Integration
|--------------------------------------------------------------------------
|
| These routes are used by the NCMAZ shell to interact with OpenCore
| via the WASM micro-app layer.
|
*/

Route::middleware(['api'])->prefix('v1')->group(function () {

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'OpenCore Business Suite',
            'timestamp' => now()->toIso8601String()
        ]);
    });

    // Dashboard stats for WASM module
    Route::get('/dashboard/stats', function () {
        return response()->json([
            'total_customers' => 1250,
            'active_subscriptions' => 980,
            'mrr' => 45000.00,
            'churn_rate' => 2.3,
            'new_signups_month' => 45
        ]);
    });

    // Customer management
    Route::get('/customers/{id}', function ($id) {
        return response()->json([
            'id' => (int)$id,
            'name' => 'Enterprise Corp',
            'email' => 'contact@enterprise.com',
            'plan' => 'enterprise',
            'desktops' => 50,
            'billing' => [
                'amount' => 2500.00,
                'status' => 'active',
                'next_billing_date' => now()->addMonth()->toDateString()
            ]
        ]);
    });

    Route::get('/customers', function () {
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Acme Corp',
                    'plan' => 'enterprise',
                    'status' => 'active'
                ],
                [
                    'id' => 2,
                    'name' => 'TechStart Inc',
                    'plan' => 'professional',
                    'status' => 'active'
                ]
            ],
            'total' => 2
        ]);
    });

    // Billing endpoints
    Route::get('/billing/invoices', function () {
        return response()->json([
            'data' => [
                [
                    'id' => 'INV-001',
                    'customer' => 'Acme Corp',
                    'amount' => 2500.00,
                    'status' => 'paid',
                    'date' => now()->subDays(5)->toDateString()
                ]
            ]
        ]);
    });

    // Analytics endpoints
    Route::get('/analytics/revenue', function () {
        return response()->json([
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [42000, 43500, 44200, 45000, 44800, 45000]
        ]);
    });

    // Module configuration
    Route::get('/config', function () {
        return response()->json([
            'features' => [
                'multi_tenant' => true,
                'api_access' => true,
                'custom_branding' => true,
                'advanced_analytics' => true
            ],
            'version' => '5.0.1',
            'wasm_compatible' => true
        ]);
    });
});
