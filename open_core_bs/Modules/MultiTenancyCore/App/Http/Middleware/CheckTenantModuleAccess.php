<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * Checks if the tenant's subscription plan includes access to the module
     * being accessed. Module is determined from the controller's namespace.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if not in SaaS mode
        if (! isSaaSMode()) {
            return $next($request);
        }

        // Get tenant from app container (set by TenantManager) - primary source
        $tenant = app()->has('tenant') ? app('tenant') : null;

        // Fallback to request (in case IdentifyTenant used request->merge)
        if (! $tenant instanceof Tenant) {
            $tenant = $request->tenant ?? null;
        }

        // Skip if no tenant context (central domain or excluded routes)
        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        // Resolve module from controller namespace
        $moduleName = $this->resolveModuleName($request);

        // Core app routes (null) are allowed through
        if (! $moduleName) {
            return $next($request);
        }

        // Check if it's a core module (always accessible)
        if (isCoreModule($moduleName)) {
            return $next($request);
        }

        // Check if it's a system module (admin-only, tenants should not access)
        if (isSystemModule($moduleName)) {
            Log::warning('CheckTenantModuleAccess: Tenant attempted to access system module', [
                'tenant_id' => $tenant->id,
                'module' => $moduleName,
                'url' => $request->path(),
            ]);

            return $this->denyAccess($request, $moduleName, 'system');
        }

        // Check if tenant's plan includes this addon module
        if (! $this->tenantHasModuleAccess($tenant, $moduleName)) {
            Log::info('CheckTenantModuleAccess: Access denied - module not in plan', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'module' => $moduleName,
                'url' => $request->path(),
            ]);

            return $this->denyAccess($request, $moduleName, 'plan');
        }

        return $next($request);
    }

    /**
     * Resolve module name from the current route's controller namespace.
     *
     * Extracts the module name directly from the controller's fully qualified
     * class name, eliminating the need for manual route-to-module mapping.
     *
     * Controller pattern: Modules\{ModuleName}\App\Http\Controllers\*
     *
     * @return string|null The module name or null if not a module route
     */
    protected function resolveModuleName(Request $request): ?string
    {
        $route = $request->route();

        if (! $route) {
            return null;
        }

        $controllerClass = $route->getControllerClass();

        if (! $controllerClass) {
            return null;
        }

        // Extract module name from namespace: Modules\{ModuleName}\...
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $controllerClass, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if tenant's plan includes the module.
     */
    protected function tenantHasModuleAccess(Tenant $tenant, string $moduleName): bool
    {
        // Get active subscription with plan
        $subscription = $tenant->activeSubscription()->with('plan')->first();

        if (! $subscription || ! $subscription->plan) {
            // No active subscription = no addon access
            return false;
        }

        return $subscription->plan->hasModule($moduleName);
    }

    /**
     * Return access denied response.
     */
    protected function denyAccess(Request $request, string $moduleName, string $reason): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->getDenialMessage($moduleName, $reason),
                'error_code' => 'MODULE_ACCESS_DENIED',
                'module' => $moduleName,
            ], 403);
        }

        return response()->view('multitenancycore::errors.module_access_denied', [
            'moduleName' => $moduleName,
            'reason' => $reason,
        ], 403);
    }

    /**
     * Get user-friendly denial message.
     */
    protected function getDenialMessage(string $moduleName, string $reason): string
    {
        if ($reason === 'system') {
            return __('Access to this feature is restricted.');
        }

        return __('The :module module is not included in your current plan. Please upgrade to access this feature.', [
            'module' => $this->getModuleDisplayName($moduleName),
        ]);
    }

    /**
     * Get display-friendly module name.
     */
    protected function getModuleDisplayName(string $moduleName): string
    {
        // Convert PascalCase to space-separated words
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $moduleName);
    }
}
