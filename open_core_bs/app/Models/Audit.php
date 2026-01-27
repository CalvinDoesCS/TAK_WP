<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * Custom Audit model with explicit relationship type hints for static analysis.
 *
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $auditable
 */
class Audit extends BaseAudit
{
    /**
     * Get the user that performed the audited action.
     *
     * This method overrides the parent to provide proper return type hints
     * for static analysis tools like Larastan.
     */
    public function user(): MorphTo
    {
        return parent::user();
    }

    /**
     * Get the auditable model.
     *
     * This method overrides the parent to provide proper return type hints
     * for static analysis tools like Larastan.
     */
    public function auditable(): MorphTo
    {
        return parent::auditable();
    }
}
