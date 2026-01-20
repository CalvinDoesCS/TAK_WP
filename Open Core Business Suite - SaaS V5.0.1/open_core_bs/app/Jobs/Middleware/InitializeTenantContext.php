<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class InitializeTenantContext
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        // Skip if MultiTenancyCore module is disabled (standalone mode)
        if (! isSaaSMode()) {
            return $next($job);
        }

        $tenantId = $job->payload()['tenant_id'] ?? null;

        if (! $tenantId) {
            // No tenant context, run in central context
            return $next($job);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            Log::error('Tenant not found for job', [
                'tenant_id' => $tenantId,
                'job' => get_class($job),
            ]);

            return; // Skip job if tenant not found
        }

        // Execute job in tenant context
        return app(TenantManager::class)->forTenant($tenant, function () use ($job, $next, $tenant) {
            Log::info('Executing job in tenant context', [
                'tenant_id' => $tenant->id,
                'tenant_subdomain' => $tenant->subdomain,
                'job' => get_class($job),
            ]);

            return $next($job);
        });
    }
}
