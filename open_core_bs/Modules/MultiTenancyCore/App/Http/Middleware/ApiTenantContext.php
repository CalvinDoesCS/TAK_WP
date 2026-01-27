<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiTenantContext
{
    public function __construct(protected TenantManager $tenantManager) {}

    /**
     * Handle an incoming API request and establish tenant context.
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

        $tenant = null;

        // Priority 1: Check X-Tenant-ID header (for mobile apps)
        $tenantIdFromHeader = $request->header('X-Tenant-ID');
        if ($tenantIdFromHeader) {
            Log::debug('API X-Tenant-ID header detected', ['tenant_id' => $tenantIdFromHeader]);

            $tenant = Tenant::where('subdomain', $tenantIdFromHeader)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                Log::warning('API tenant not found from header', ['tenant_id' => $tenantIdFromHeader]);

                return response()->json([
                    'success' => false,
                    'message' => 'Organization not found.',
                    'error_code' => 'TENANT_NOT_FOUND',
                ], 404);
            }
        }

        // Priority 2: Extract tenant from JWT token (for authenticated requests without header)
        if (! $tenant) {
            $tenant = $this->extractTenantFromJwt($request);
        }

        // Priority 3: Fall back to subdomain detection (for web browser access)
        if (! $tenant) {
            $tenant = $this->identifyTenantFromSubdomain($request);
        }

        // If tenant was identified, validate and switch context
        if ($tenant) {
            Log::debug('API tenant found', ['tenant_id' => $tenant->id, 'name' => $tenant->name]);

            // Check if tenant has active subscription
            if (! $tenant->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription has expired. Please renew your subscription.',
                    'error_code' => 'SUBSCRIPTION_EXPIRED',
                ], 403);
            }

            // Check if database is provisioned
            if ($tenant->database_provisioning_status !== 'provisioned') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your workspace is being prepared. Please try again in a few moments.',
                    'error_code' => 'DATABASE_NOT_READY',
                ], 503);
            }

            // Switch to tenant context using TenantManager
            $this->tenantManager->switchToTenant($tenant);

            // CRITICAL: Forget all resolved auth guards to force re-fetch from tenant database
            // This ensures the user is resolved from the correct database after tenant switch
            Auth::forgetGuards();

            // Add tenant to request
            $request->merge(['tenant' => $tenant]);
        }

        return $next($request);
    }

    /**
     * Identify tenant from subdomain in the request host.
     */
    protected function identifyTenantFromSubdomain(Request $request): ?Tenant
    {
        // Extract subdomain from the request
        $fullHost = $request->getHost();

        // Remove port if present for comparison
        $host = explode(':', $fullHost)[0];
        $appDomain = config('multitenancycore.central_domain', parse_url(config('app.url'), PHP_URL_HOST));
        $appDomain = explode(':', $appDomain)[0]; // Remove port from app domain too

        // Debug logging
        Log::debug('API Tenant context middleware - subdomain check', [
            'fullHost' => $fullHost,
            'host' => $host,
            'appDomain' => $appDomain,
            'url' => $request->fullUrl(),
        ]);

        // Determine if we need to check for tenant
        $subdomain = null;

        // Check if this is a subdomain request
        if ($host !== $appDomain && str_ends_with($host, ".{$appDomain}")) {
            $subdomain = str_replace(".{$appDomain}", '', $host);
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
                }
            }
        }

        if ($subdomain) {
            Log::debug('API subdomain detected', ['subdomain' => $subdomain]);

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

            return $tenant;
        }

        return null;
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
     * Extract tenant from JWT token claims.
     *
     * This method attempts to read the tenant_subdomain claim from the JWT token.
     * It's used as a fallback when X-Tenant-ID header is not present.
     * Full token validation happens in the auth:api middleware later.
     */
    protected function extractTenantFromJwt(Request $request): ?Tenant
    {
        try {
            $token = $request->bearerToken();
            if (! $token) {
                return null;
            }

            // Decode JWT to read claims (full validation happens in auth:api middleware)
            $payload = JWTAuth::setToken($token)->getPayload();
            $tenantSubdomain = $payload->get('tenant_subdomain');

            if ($tenantSubdomain) {
                Log::debug('Tenant subdomain extracted from JWT', ['subdomain' => $tenantSubdomain]);

                return Tenant::where('subdomain', $tenantSubdomain)
                    ->where('status', 'active')
                    ->first();
            }
        } catch (\Exception $e) {
            // JWT decode failed - this is normal for invalid/expired tokens
            // Let auth:api middleware handle the actual authentication error
            Log::debug('JWT decode for tenant extraction failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
