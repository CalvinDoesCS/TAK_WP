<?php

use Illuminate\Support\Facades\Storage;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

if (! function_exists('tenant')) {
    /**
     * Get the current tenant instance or tenant manager
     *
     * @return Tenant|TenantManager|null
     */
    function tenant()
    {
        $manager = app('tenant.manager');

        // If no arguments, return current tenant
        return $manager->getTenant();
    }
}

if (! function_exists('tenantManager')) {
    /**
     * Get the tenant manager instance
     */
    function tenantManager(): TenantManager
    {
        return app('tenant.manager');
    }
}

if (! function_exists('isTenant')) {
    /**
     * Check if currently in tenant context
     */
    function isTenant(): bool
    {
        return app('tenant.manager')->isTenantContext();
    }
}

if (! function_exists('tenantUrl')) {
    /**
     * Generate URL for tenant subdomain
     */
    function tenantUrl(string $path = '', ?Tenant $tenant = null): string
    {
        $tenant = $tenant ?: tenant();

        if (! $tenant) {
            return url($path);
        }

        $appUrl = config('app.url');
        $parsedUrl = parse_url($appUrl);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '';

        $baseUrl = $scheme.'://'.$tenant->subdomain.'.'.$host.$port;

        return $baseUrl.'/'.ltrim($path, '/');
    }
}

if (! function_exists('current_tenant')) {
    /**
     * Get the current tenant (alias for tenant())
     *
     * @return \Modules\MultiTenancyCore\App\Models\Tenant|null
     */
    function current_tenant()
    {
        return tenant();
    }
}

if (! function_exists('is_tenant_context')) {
    /**
     * Check if we're in a tenant context (alias for isTenant())
     *
     * @return bool
     */
    function is_tenant_context()
    {
        return isTenant();
    }
}

if (! function_exists('isSaaSMode')) {
    /**
     * Check if MultiTenancyCore module is enabled (SaaS mode)
     */
    function isSaaSMode(): bool
    {
        return \Module::find('MultiTenancyCore')?->isEnabled() ?? false;
    }
}

if (! function_exists('tenantDisk')) {
    /**
     * Get the appropriate storage disk based on tenant context
     */
    function tenantDisk(): string
    {
        return tenant() ? 'tenant' : 'public';
    }
}

if (! function_exists('tenantStorage')) {
    /**
     * Get Storage instance for tenant disk
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    function tenantStorage()
    {
        return Storage::disk(tenantDisk());
    }
}
