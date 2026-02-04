<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use UserActionsTrait;

    protected $fillable = [
        'user_id',
        'notification_type',
        'preferences',
        'fcm_enabled',
        'mail_enabled',
        'database_enabled',
        'broadcast_enabled',
    ];

    protected $casts = [
        'preferences' => 'array',
        'fcm_enabled' => 'boolean',
        'mail_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'broadcast_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the notification preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a specific channel is enabled.
     *
     * @param  string  $channel  (fcm, mail, database, broadcast)
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'fcm' => $this->fcm_enabled,
            'mail' => $this->mail_enabled,
            'database' => $this->database_enabled,
            'broadcast' => $this->broadcast_enabled,
            default => false,
        };
    }

    /**
     * Get all enabled channels.
     */
    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->fcm_enabled) {
            $channels[] = 'fcm';
        }
        if ($this->mail_enabled) {
            $channels[] = 'mail';
        }
        if ($this->database_enabled) {
            $channels[] = 'database';
        }
        if ($this->broadcast_enabled) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Enable a specific channel.
     */
    public function enableChannel(string $channel): void
    {
        match ($channel) {
            'fcm' => $this->fcm_enabled = true,
            'mail' => $this->mail_enabled = true,
            'database' => $this->database_enabled = true,
            'broadcast' => $this->broadcast_enabled = true,
            default => null,
        };
    }

    /**
     * Disable a specific channel.
     */
    public function disableChannel(string $channel): void
    {
        match ($channel) {
            'fcm' => $this->fcm_enabled = false,
            'mail' => $this->mail_enabled = false,
            'database' => $this->database_enabled = false,
            'broadcast' => $this->broadcast_enabled = false,
            default => null,
        };
    }
}
