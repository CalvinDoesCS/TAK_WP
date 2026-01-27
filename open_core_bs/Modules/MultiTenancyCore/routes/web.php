<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| MultiTenancyCore module routes
|
*/

// Public routes (no auth required) - Tenant Registration
Route::middleware(['web'])->name('multitenancycore.')->group(function () {
    // Clean registration routes (no prefix for better UX)
    Route::get('/register', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\RegistrationController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\RegistrationController::class, 'register'])->name('register.post');
    Route::post('/register/validate', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\RegistrationController::class, 'validateField'])->name('register.validate');
});

// Tenant portal routes - clean URLs without /multitenancy prefix
Route::prefix('tenant')->name('multitenancycore.tenant.')->middleware(['auth', 'web', 'ensure.tenant', 'tenant.portal.layout'])->group(function () {
    Route::get('/dashboard', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\DashboardController::class, 'index'])->name('dashboard');

    // Plan Selection (for new tenants or tenants without subscription)
    Route::get('/plan-selection', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\PlanSelectionController::class, 'index'])->name('plan-selection');
    Route::post('/plan-selection', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\PlanSelectionController::class, 'selectPlan'])->name('plan-selection.submit');
    Route::get('/plan-selection/payment/{payment}/instructions', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\PlanSelectionController::class, 'showPaymentInstructions'])->name('plan-selection.payment-instructions');

    // Pending approval page (for tenants awaiting manual approval)
    Route::get('/pending-approval', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\DashboardController::class, 'pendingApproval'])->name('pending-approval');

    Route::get('/subscription', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'index'])->name('subscription');
    Route::get('/subscription/select-plan/{plan}', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'selectPlan'])->name('subscription.select-plan');
    Route::post('/subscription/change-plan', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');
    Route::post('/subscription/process-change-plan', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'processChangePlan'])->name('subscription.process-change-plan');
    Route::post('/subscription/cancel', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::get('/subscription/invoice/{payment}/download', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice.download');
    Route::get('/billing', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\BillingController::class, 'index'])->name('billing');
    Route::get('/payment/{payment}/instructions', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\BillingController::class, 'paymentInstructions'])->name('payment.instructions');
    Route::post('/payment/{payment}/upload-proof', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\BillingController::class, 'uploadProof'])->name('payment.upload-proof');
    Route::get('/payment/{payment}/proof', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\BillingController::class, 'viewProof'])->name('payment.proof');
    Route::get('/payment/{payment}/details', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\BillingController::class, 'getPaymentDetails'])->name('payment.details');
    Route::get('/invoices', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\InvoiceController::class, 'index'])->name('invoices');
    Route::get('/invoices/{id}', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{id}/download', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/usage', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\UsageController::class, 'index'])->name('usage');
    Route::get('/profile', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/support', [\Modules\MultiTenancyCore\App\Http\Controllers\Tenant\SupportController::class, 'index'])->name('support');
});

// Admin routes - clean URLs for MultiTenancyCore admin
Route::prefix('multitenancy/admin')->name('multitenancycore.admin.')->middleware(['auth', 'web', 'role:super_admin|admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // SaaS Settings (Super Admin and Admin - inherits from parent middleware)
    Route::prefix('saas-settings')->name('saas-settings.')->group(function () {
        Route::get('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SaasSettingsController::class, 'index'])->name('index');
        Route::post('/update-offline', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SaasSettingsController::class, 'updateOfflinePayment'])->name('update-offline');
        Route::post('/toggle-gateway', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SaasSettingsController::class, 'toggleGateway'])->name('toggle-gateway');
        Route::post('/update-general', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SaasSettingsController::class, 'updateGeneralSettings'])->name('update-general');
    });

    // Email Templates (Super Admin only)
    Route::prefix('email-templates')->name('email-templates.')->middleware(['role:super_admin|admin'])->group(function () {
        Route::put('/{id}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('update');
        Route::post('/{id}/test', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\EmailTemplateController::class, 'test'])->name('test');
    });

    // Tenant Management
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('index');
        Route::get('/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'indexAjax'])->name('datatable');
        Route::get('/approval-queue', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'approvalQueue'])->name('approval-queue');
        Route::get('/approval-queue/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'approvalQueueAjax'])->name('approval-queue.datatable');
        Route::get('/{tenant}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'show'])->name('show');
        Route::get('/{tenant}/edit', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('edit');
        Route::post('/{tenant}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('update');
        Route::post('/{tenant}/approve', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'approve'])->name('approve');
        Route::post('/{tenant}/reject', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'reject'])->name('reject');
        Route::post('/{tenant}/suspend', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'suspend'])->name('suspend');
        Route::post('/{tenant}/activate', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantController::class, 'activate'])->name('activate');
    });

    // Plan Management
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'index'])->name('index');
        Route::get('/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'create'])->name('create');
        Route::get('/{plan}/edit', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'edit'])->name('edit');
        Route::get('/{plan}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'show'])->name('show');
        Route::post('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'store'])->name('store');
        Route::put('/{plan}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PlanController::class, 'destroy'])->name('destroy');
    });

    // Subscription Management
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('index');
        Route::get('/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'indexAjax'])->name('datatable');
        Route::get('/create', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'create'])->name('create');
        Route::post('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/renew', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/change-plan', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'changePlan'])->name('change-plan');
        Route::get('/api/expiring', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\SubscriptionController::class, 'getExpiring'])->name('expiring');
    });

    // Payment Management
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/approval-queue', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'index'])->name('approval-queue');
        Route::get('/approval-queue/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'indexAjax'])->name('approval-queue.datatable');
        Route::get('/history', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'history'])->name('history');
        Route::get('/history/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'historyAjax'])->name('history.datatable');
        Route::get('/statistics', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'statistics'])->name('statistics');
        Route::get('/{payment}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'show'])->name('show');
        Route::get('/{payment}/proof', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'viewProof'])->name('proof');
        Route::post('/{payment}/approve', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'approve'])->name('approve');
        Route::post('/{payment}/reject', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'reject'])->name('reject');
        Route::post('/{payment}/upload-proof', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\PaymentApprovalController::class, 'uploadProof'])->name('upload-proof');
    });

    // Tenant Database Provisioning
    Route::prefix('provisioning')->name('provisioning.')->group(function () {
        Route::get('/', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'index'])->name('index');
        Route::get('/datatable', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'getDataAjax'])->name('datatable');
        Route::get('/history', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'getHistoryAjax'])->name('history');
        Route::get('/statistics', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'statistics'])->name('statistics');
        Route::get('/{tenant}', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'show'])->name('show');
        Route::post('/{tenant}/auto-provision', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'autoProvision'])->name('auto-provision');
        Route::post('/{tenant}/manual-provision', [\Modules\MultiTenancyCore\App\Http\Controllers\Admin\TenantProvisioningController::class, 'manualProvision'])->name('manual-provision');
    });
});
