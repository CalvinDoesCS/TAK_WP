<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for all tenant-specific models
 * This ensures they use the tenant connection
 */
abstract class TenantModel extends Model
{
    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        // Use tenant connection if we're in tenant context
        if (app()->has('tenant')) {
            return 'tenant';
        }
        
        // Otherwise use the default connection
        return $this->connection;
    }
}