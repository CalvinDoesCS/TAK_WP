<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class IdentifyTenant
{
    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip if MultiTenancyCore is disabled
        if (! isSaaSMode()) {
            return $next($request);
        }

        // Check if current route should be excluded from tenant identification
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // Extract subdomain from the request
        $fullHost = $request->getHost();

        // Remove port if present for comparison
        $host = explode(':', $fullHost)[0];
        $appDomain = config('multitenancycore.central_domain', parse_url(config('app.url'), PHP_URL_HOST));
        $appDomain = explode(':', $appDomain)[0]; // Remove port from app domain too

        // Debug logging
        Log::info('Tenant identification middleware', [
            'fullHost' => $fullHost,
            'host' => $host,
            'appDomain' => $appDomain,
            'url' => $request->fullUrl(),
        ]);

        // Determine if we need to check for tenant
        $isSubdomainRequest = false;
        $subdomain = null;

        // Check if this is a subdomain request
        if ($host !== $appDomain && str_ends_with($host, ".{$appDomain}")) {
            $subdomain = str_replace(".{$appDomain}", '', $host);
            $isSubdomainRequest = true;
        }
        // Also check if host contains a subdomain pattern (e.g., acme.192.168.0.20)
        elseif (str_contains($host, '.') && $host !== $appDomain) {
            $parts = explode('.', $host);
            // Check if first part could be a subdomain
            if (count($parts) > 1) {
                $potentialSubdomain = $parts[0];
                // Check if it's not just the IP address
                if (! is_numeric($potentialSubdomain)) {
                    $subdomain = $potentialSubdomain;
                    $isSubdomainRequest = true;
                }
            }
        }

        if ($isSubdomainRequest && $subdomain) {
            Log::info('Subdomain detected', ['subdomain' => $subdomain]);

            // Find tenant by subdomain
            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                // Check for custom domain
                $tenant = Tenant::where('custom_domain', $host)
                    ->where('status', 'active')
                    ->first();
            }

            if ($tenant) {
                Log::info('Tenant found', ['tenant_id' => $tenant->id, 'name' => $tenant->name]);

                // Check if tenant has active subscription
                if (! $tenant->hasActiveSubscription()) {
                    return response()->view('multitenancycore::errors.subscription_expired', [], 403);
                }

                // Check if database is provisioned
                if ($tenant->database_provisioning_status !== 'provisioned') {
                    return response()->view('multitenancycore::errors.database_not_ready', [], 503);
                }

                // Switch to tenant context using TenantManager
                $this->tenantManager->switchToTenant($tenant);

                // Share tenant with views
                view()->share('currentTenant', $tenant);

                // Add tenant to request
                $request->merge(['tenant' => $tenant]);

                // Check module access for this tenant
                $moduleAccessResponse = $this->checkModuleAccess($request, $tenant);
                if ($moduleAccessResponse !== null) {
                    return $moduleAccessResponse;
                }
            } else {
                Log::warning('Tenant not found', ['subdomain' => $subdomain, 'host' => $host]);

                return response()->view('multitenancycore::errors.tenant_not_found', ['subdomain' => $subdomain], 404);
            }
        }

        return $next($request);
    }

    /**
     * Check if the request should be excluded from tenant identification
     */
    protected function shouldExclude(Request $request): bool
    {
        $excludedRoutes = config('multitenancycore.excluded_routes', []);

        foreach ($excludedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if tenant has access to the module being accessed.
     *
     * @return \Illuminate\Http\Response|null Returns response if access denied, null if allowed
     */
    protected function checkModuleAccess(Request $request, Tenant $tenant)
    {
        // Get the route
        $route = $request->route();
        if (! $route) {
            return null;
        }

        // Get controller class
        $controllerClass = $route->getControllerClass();
        if (! $controllerClass) {
            return null;
        }

        // Extract module name from controller namespace: Modules\{ModuleName}\...
        if (! preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $controllerClass, $matches)) {
            return null; // Not a module route
        }

        $moduleName = $matches[1];

        // Core modules are always accessible
        if (isCoreModule($moduleName)) {
            return null;
        }

        // System modules are admin-only
        if (isSystemModule($moduleName)) {
            return $this->denyModuleAccess($request, $moduleName, 'system');
        }

        // Check if tenant's plan includes this module
        $subscription = $tenant->activeSubscription()->with('plan')->first();
        if (! $subscription || ! $subscription->plan) {
            return $this->denyModuleAccess($request, $moduleName, 'plan');
        }

        if (! $subscription->plan->hasModule($moduleName)) {
            return $this->denyModuleAccess($request, $moduleName, 'plan');
        }

        return null; // Access allowed
    }

    /**
     * Return access denied response for module access.
     */
    protected function denyModuleAccess(Request $request, string $moduleName, string $reason)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $reason === 'system'
                    ? __('Access to this feature is restricted.')
                    : __('The :module module is not included in your current plan.', [
                        'module' => preg_replace('/(?<!^)([A-Z])/', ' $1', $moduleName),
                    ]),
                'error_code' => 'MODULE_ACCESS_DENIED',
                'module' => $moduleName,
            ], 403);
        }

        return response()->view('multitenancycore::errors.module_access_denied', [
            'moduleName' => $moduleName,
            'reason' => $reason,
        ], 403);
    }
}
