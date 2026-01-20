<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserStatusModel extends Model implements AuditableContract
{
    use Auditable, SoftDeletes, UserActionsTrait;

    protected $table = 'user_statuses';

    protected $fillable = [
        'user_id',
        'status',
        'message',
        'expires_at',
        'created_by_id',
        'updated_by_id',
        'tenant_id',
    ];

    protected $casts = [
        'status' => UserStatus::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the status
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if status has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope to get active statuses (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            UserStatus::ONLINE => 'success',
            UserStatus::BUSY => 'warning',
            UserStatus::AWAY => 'info',
            UserStatus::ON_CALL => 'primary',
            UserStatus::DO_NOT_DISTURB => 'danger',
            UserStatus::ON_LEAVE => 'secondary',
            UserStatus::ON_MEETING => 'warning',
            UserStatus::OFFLINE => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the status icon for UI display
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            UserStatus::ONLINE => 'bx-circle',
            UserStatus::BUSY => 'bx-time',
            UserStatus::AWAY => 'bx-moon',
            UserStatus::ON_CALL => 'bx-phone-call',
            UserStatus::DO_NOT_DISTURB => 'bx-minus-circle',
            UserStatus::ON_LEAVE => 'bx-calendar-x',
            UserStatus::ON_MEETING => 'bx-group',
            UserStatus::OFFLINE => 'bx-x-circle',
            default => 'bx-circle',
        };
    }
}
