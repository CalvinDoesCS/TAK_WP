<?php

namespace App\Notifications;

use App\Enums\NotificationPreferenceType;
use App\Helpers\NotificationPreferenceHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base notification class with preference checking
 *
 * All application notifications should extend this class to benefit from:
 * - Automatic channel filtering based on user preferences
 * - Consistent notification structure
 * - Queue support
 */
abstract class BaseNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification preference type for this notification.
     * Must be implemented by child classes.
     */
    abstract protected function getNotificationPreferenceType(): NotificationPreferenceType;

    /**
     * Get the default notification channels.
     * Override this in child classes to customize default channels.
     */
    protected function getDefaultChannels(): array
    {
        return ['fcm', 'database'];
    }

    /**
     * Get the notification's delivery channels, filtered by user preferences.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        $preferenceType = $this->getNotificationPreferenceType();

        // Check if user has specific preferences for this notification type
        $preference = $notifiable->notificationPreferences()
            ->where('notification_type', $preferenceType->value)
            ->first();

        if ($preference) {
            // User has specific preferences - use them
            return $preference->getEnabledChannels();
        }

        // No specific preference - use defaults for this notification type
        $defaults = NotificationPreferenceHelper::getDefaultChannels($preferenceType);
        $enabledChannels = [];

        foreach ($defaults as $channel => $enabled) {
            if ($enabled) {
                $enabledChannels[] = $channel;
            }
        }

        return $enabledChannels;
    }

    /**
     * Determine if the notification should be sent.
     * Override this to add custom logic.
     *
     * @param  mixed  $notifiable
     */
    public function shouldSend($notifiable): bool
    {
        return true;
    }

    /**
     * Get common notification data for all channels.
     * Override this to provide common data structure.
     */
    protected function getCommonData(): array
    {
        return [];
    }

    /**
     * Helper method to format user data for notifications.
     *
     * @param  mixed  $user
     */
    protected function formatUser($user): array
    {
        if (! $user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->getFullName(),
            'email' => $user->email,
            'profile_picture' => $user->getProfilePicture(),
        ];
    }

    /**
     * Helper method to truncate text for notifications.
     */
    protected function truncate(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).'...';
    }

    /**
     * Get queue connection for this notification.
     * Override to customize queue handling.
     *
     * @return string|null
     */
    public function viaConnections(): array
    {
        return [
            'fcm' => 'sync',      // FCM sent immediately for real-time
            'database' => 'sync', // Database notifications are fast
            'mail' => 'default',  // Email can be queued
            'broadcast' => 'sync', // Broadcasting should be immediate
        ];
    }
}
