<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait for handling cross-module relationships safely.
 *
 * This trait provides methods to define Eloquent relationships to models
 * in other modules that may or may not be installed. When the target module
 * is not installed/enabled, these methods return null-returning relationships
 * instead of throwing class not found errors.
 */
trait HasCrossModuleRelationships
{
    /**
     * Create a safe belongsTo relationship that handles missing modules gracefully.
     *
     * @param  string  $module  The module name to check (e.g., 'SiteAttendance')
     * @param  string  $relatedClass  Fully qualified class name (e.g., 'Modules\\SiteAttendance\\App\\Models\\Site')
     * @param  string  $foreignKey  The foreign key column name
     * @param  string|null  $ownerKey  The owner key on the related model (defaults to 'id')
     */
    protected function safeBelongsTo(
        string $module,
        string $relatedClass,
        string $foreignKey,
        ?string $ownerKey = null
    ): BelongsTo {
        if (! moduleExists($module)) {
            // Return a null relationship - always returns null when accessed
            return $this->belongsTo(static::class, $foreignKey)->whereRaw('1=0');
        }

        return $this->belongsTo($relatedClass, $foreignKey, $ownerKey);
    }

    /**
     * Create a safe hasMany relationship that handles missing modules gracefully.
     *
     * @param  string  $module  The module name to check
     * @param  string  $relatedClass  Fully qualified class name
     * @param  string  $foreignKey  The foreign key column name
     * @param  string|null  $localKey  The local key (defaults to 'id')
     */
    protected function safeHasMany(
        string $module,
        string $relatedClass,
        string $foreignKey,
        ?string $localKey = null
    ): HasMany {
        if (! moduleExists($module)) {
            // Return an empty relationship
            return $this->hasMany(static::class, 'id', 'id')->whereRaw('1=0');
        }

        return $this->hasMany($relatedClass, $foreignKey, $localKey);
    }

    /**
     * Create a safe hasOne relationship that handles missing modules gracefully.
     *
     * @param  string  $module  The module name to check
     * @param  string  $relatedClass  Fully qualified class name
     * @param  string  $foreignKey  The foreign key column name
     * @param  string|null  $localKey  The local key (defaults to 'id')
     */
    protected function safeHasOne(
        string $module,
        string $relatedClass,
        string $foreignKey,
        ?string $localKey = null
    ): HasOne {
        if (! moduleExists($module)) {
            // Return a null relationship
            return $this->hasOne(static::class, 'id', 'id')->whereRaw('1=0');
        }

        return $this->hasOne($relatedClass, $foreignKey, $localKey);
    }
}
