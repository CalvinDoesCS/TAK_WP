<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserAvailableLeave extends Model implements AuditableContract
{
    use Auditable, SoftDeletes, UserActionsTrait;

    protected $table = 'users_available_leaves';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'entitled_leaves',
        'carried_forward_leaves',
        'additional_leaves',
        'used_leaves',
        'available_leaves',
        'carry_forward_expiry_date',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'entitled_leaves' => 'float',
        'carried_forward_leaves' => 'decimal:2',
        'additional_leaves' => 'decimal:2',
        'used_leaves' => 'float',
        'available_leaves' => 'float',
        'carry_forward_expiry_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
