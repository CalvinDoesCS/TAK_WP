<?php

use Illuminate\Support\Facades\Route;
use Modules\MultiTenancyCore\App\Http\Controllers\Api\OrganizationController;
use Modules\MultiTenancyCore\App\Http\Controllers\Api\TenantController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

// Public API routes (no authentication required)
Route::prefix('V1')->group(function () {
    Route::post('organization/lookup', [OrganizationController::class, 'lookup']);
});

// Protected API routes (require authentication and tenant context)
Route::middleware(['auth:api', 'api.tenant.context'])->prefix('V1')->group(function () {
    Route::get('tenant/info', [TenantController::class, 'info']);
});
