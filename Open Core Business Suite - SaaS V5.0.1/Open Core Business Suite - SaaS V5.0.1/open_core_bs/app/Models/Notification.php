<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use UserActionsTrait;

    protected $table = 'notifications';

    /**
     * Indicates if the IDs are auto-incrementing.
     * Laravel's notifications table uses UUID primary keys.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'notifiable_id',
        'notifiable_type',
        'data',
        'read_at',
    ];

    /**
     * Get the notifiable entity (user) that the notification belongs to.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the notification.
     * This is an alias for notifiable() when the notifiable is a User.
     */
    public function user(): MorphTo
    {
        return $this->notifiable();
    }

    public function getTypeString(): string
    {
        return match ($this->type) {
            'App\Notifications\Leave\NewLeaveRequest', 'App\Notifications\Leave\CancelLeaveRequest', 'App\Notifications\Expense\CancelExpenseRequest', 'App\Notifications\Expense\NewExpenseRequest' => 'Approvals',
            'App\Notifications\Alerts\BreakAlert', 'App\Notifications\NewVisit' => 'Alerts',
            'App\Notifications\Chat\NewChatMessage' => 'Chat',
            'App\Notifications\Attendance\CheckInOut' => 'Attendance',
            default => 'System Notification',
        };
    }
}
