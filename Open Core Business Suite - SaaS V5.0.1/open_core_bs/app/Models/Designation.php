<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Designation extends Model implements AuditableContract
{
    use Auditable, SoftDeletes, UserActionsTrait;

    protected $table = 'designations';

    protected $fillable = [
        'name',
        'code',
        'notes',
        'status',
        'department_id',
        'parent_id',
        'level',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        //
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Designation::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'designation_id');
    }
}
