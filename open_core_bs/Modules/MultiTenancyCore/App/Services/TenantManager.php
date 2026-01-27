<?php

namespace Modules\MultiTenancyCore\App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\MultiTenancyCore\App\Models\Tenant;

class TenantManager
{
    protected $currentTenant = null;

    protected $originalConnection = null;

    /**
     * Get the current tenant
     */
    public function getTenant(): ?Tenant
    {
        return $this->currentTenant ?? app()->has('tenant') ? app('tenant') : null;
    }

    /**
     * Set the current tenant
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        if ($tenant) {
            app()->instance('tenant', $tenant);
        } else {
            app()->forgetInstance('tenant');
        }
    }

    /**
     * Check if in tenant context
     */
    public function isTenantContext(): bool
    {
        return $this->getTenant() !== null;
    }

    /**
     * Switch to central database
     */
    public function switchToCentral(): void
    {
        if (! $this->originalConnection) {
            $this->originalConnection = config('database.default');
        }

        Config::set('database.default', $this->originalConnection ?: 'mysql');

        // Reset cache prefix to default
        Config::set('cache.prefix', config('cache.prefix_original', ''));

        DB::purge('tenant');
        DB::reconnect(config('database.default'));
    }

    /**
     * Switch to tenant database
     */
    public function switchToTenant(Tenant $tenant): void
    {
        // Store previous default connection name before switching
        $previousDefault = config('database.default');

        if (! $this->originalConnection) {
            $this->originalConnection = $previousDefault;
        }

        $databaseService = app(TenantDatabaseService::class);
        $connectionConfig = $databaseService->getTenantConnectionConfig($tenant);

        Config::set('database.connections.tenant', $connectionConfig);
        Config::set('database.default', 'tenant');

        // Set tenant-specific cache prefix to prevent cache data leaks
        Config::set('cache.prefix', 'tenant_'.$tenant->id.'_cache');

        // Configure tenant-specific storage
        $this->configureTenantStorage($tenant);

        // CRITICAL: Purge the OLD default connection to prevent stale connections
        // Without this, Eloquent queries may still use the cached old connection
        if ($previousDefault && $previousDefault !== 'tenant') {
            DB::purge($previousDefault);
        }

        DB::purge('tenant');
        DB::reconnect('tenant');

        $this->setTenant($tenant);
    }

    /**
     * Configure tenant-specific storage disk
     */
    protected function configureTenantStorage(Tenant $tenant): void
    {
        $tenantStoragePath = storage_path('app/tenants/'.$tenant->id);

        // Ensure tenant storage directory exists
        if (! file_exists($tenantStoragePath)) {
            mkdir($tenantStoragePath, 0755, true);
        }

        // Update tenant disk configuration
        Config::set('filesystems.disks.tenant', [
            'driver' => 'local',
            'root' => $tenantStoragePath,
            'url' => config('app.url').'/storage/tenants/'.$tenant->id,
            'visibility' => 'private',
        ]);
    }

    /**
     * Execute code in tenant context
     */
    public function forTenant(Tenant $tenant, callable $callback)
    {
        $previousTenant = $this->getTenant();
        $previousConnection = config('database.default');

        try {
            $this->switchToTenant($tenant);

            return $callback($tenant);
        } finally {
            // Restore previous state
            if ($previousTenant) {
                $this->switchToTenant($previousTenant);
            } else {
                $this->switchToCentral();
            }
        }
    }

    /**
     * Execute code in central context
     */
    public function forCentral(callable $callback)
    {
        $previousTenant = $this->getTenant();
        $previousConnection = config('database.default');

        try {
            $this->switchToCentral();
            $this->setTenant(null);

            return $callback();
        } finally {
            // Restore previous state
            if ($previousTenant) {
                $this->switchToTenant($previousTenant);
            }
        }
    }
}
