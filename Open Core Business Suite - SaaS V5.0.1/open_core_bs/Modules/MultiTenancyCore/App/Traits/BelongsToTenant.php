<?php

namespace Modules\MultiTenancyCore\App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for models that belong to a tenant
 * This automatically scopes queries to the current tenant
 */
trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant()
    {
        // Only apply tenant scope if in tenant context
        if (app()->has('tenant') && app('tenant')) {
            // No need for global scope as models use tenant database connection
            // The data is already isolated by database
        }
    }
    
    /**
     * Get the current connection name for the model
     */
    public function getConnectionName()
    {
        // Use tenant connection if in tenant context
        if (app()->has('tenant') && app('tenant')) {
            return 'tenant';
        }
        
        return $this->connection ?? config('database.default');
    }
}