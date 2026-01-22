<?php

namespace App\Helpers;

use App\Enums\NotificationPreferenceType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotificationPreferenceHelper
{
    /**
     * Available notification channels
     */
    public const CHANNELS = ['fcm', 'mail', 'database', 'broadcast'];

    /**
     * Get a user's notification preference for a specific type.
     */
    public static function getPreference(User $user, NotificationPreferenceType $type, bool $default = true): bool
    {
        $preferences = $user->notificationPreference?->preferences ?? [];
        $value = $preferences[$type->value] ?? $default;

        // Backwards compatibility: if boolean, return it
        if (is_bool($value)) {
            return $value;
        }

        // If array (new format), check if any channel is enabled
        if (is_array($value)) {
            return in_array(true, $value, true);
        }

        return $default;
    }

    /**
     * Get a user's notification preference for a specific type and channel.
     *
     * @param  string  $channel  (fcm, mail, database, broadcast)
     */
    public static function isChannelEnabled(User $user, NotificationPreferenceType $type, string $channel, bool $default = true): bool
    {
        $preferences = $user->notificationPreference?->preferences ?? [];
        $value = $preferences[$type->value] ?? null;

        // If null, use default
        if ($value === null) {
            return $default;
        }

        // Backwards compatibility: if boolean, apply to all channels
        if (is_bool($value)) {
            return $value;
        }

        // New format: check specific channel
        if (is_array($value)) {
            return $value[$channel] ?? $default;
        }

        return $default;
    }

    /**
     * Set a user's notification preference for a specific type (all channels).
     */
    public static function setPreference(User $user, NotificationPreferenceType $type, bool $value): void
    {
        $preference = $user->notificationPreference ?? new NotificationPreference(['user_id' => $user->id]);

        $preferences = $preference->preferences ?? [];
        $preferences[$type->value] = $value;

        $preference->preferences = $preferences;
        $preference->save();
    }

    /**
     * Set a user's notification preference for a specific type and channel.
     *
     * @param  string  $channel  (fcm, mail, database, broadcast)
     */
    public static function setChannelPreference(User $user, NotificationPreferenceType $type, string $channel, bool $value): void
    {
        $preference = $user->notificationPreference ?? new NotificationPreference(['user_id' => $user->id]);

        $preferences = $preference->preferences ?? [];
        $current = $preferences[$type->value] ?? [];

        // Convert old boolean format to new array format
        if (is_bool($current)) {
            $current = array_fill_keys(self::CHANNELS, $current);
        } elseif (! is_array($current)) {
            $current = array_fill_keys(self::CHANNELS, true);
        }

        // Update specific channel
        $current[$channel] = $value;
        $preferences[$type->value] = $current;

        $preference->preferences = $preferences;
        $preference->save();
    }

    /**
     * Set all channel preferences for a specific notification type.
     *
     * @param  array  $channels  ['fcm' => true, 'mail' => false, 'database' => true, 'broadcast' => false]
     */
    public static function setAllChannels(User $user, NotificationPreferenceType $type, array $channels): void
    {
        $preference = $user->notificationPreference ?? new NotificationPreference(['user_id' => $user->id]);

        $preferences = $preference->preferences ?? [];
        $preferences[$type->value] = $channels;

        $preference->preferences = $preferences;
        $preference->save();
    }

    /**
     * Get enabled channels for a specific notification type.
     *
     * @return array List of enabled channels ['fcm', 'database', 'mail']
     */
    public static function getEnabledChannels(User $user, NotificationPreferenceType $type): array
    {
        $enabled = [];

        foreach (self::CHANNELS as $channel) {
            if (self::isChannelEnabled($user, $type, $channel)) {
                $enabled[] = $channel;
            }
        }

        return $enabled;
    }

    /**
     * Notify users who have enabled a specific notification preference.
     *
     * @param  mixed  $notification
     */
    public static function notifyUsers(NotificationPreferenceType $type, $notification): void
    {
        $users = User::with('notificationPreference')
            ->whereHas('notificationPreference', function ($query) use ($type) {
                $query->whereJsonContains('preferences->'.$type->value, true);
            })
            ->get();

        Notification::send($users, $notification);
    }

    /**
     * Get all notification preferences for display.
     */
    public static function getAllPreferences(User $user): array
    {
        $preferences = $user->notificationPreference?->preferences ?? [];
        $result = [];

        foreach (NotificationPreferenceType::all() as $type) {
            $value = $preferences[$type->value] ?? null;

            // Convert to new format if needed
            if ($value === null) {
                // Default: all channels enabled
                $result[$type->value] = array_fill_keys(self::CHANNELS, true);
            } elseif (is_bool($value)) {
                // Old format: convert to new format
                $result[$type->value] = array_fill_keys(self::CHANNELS, $value);
            } else {
                // New format: ensure all channels are present
                $result[$type->value] = array_merge(
                    array_fill_keys(self::CHANNELS, true),
                    is_array($value) ? $value : []
                );
            }
        }

        return $result;
    }

    /**
     * Get default channel preferences for a notification type.
     * These defaults can be customized per notification type.
     */
    public static function getDefaultChannels(NotificationPreferenceType $type): array
    {
        // Customize defaults per notification type
        return match ($type) {
            // Chat: FCM + Database only (no email spam)
            NotificationPreferenceType::CHAT_MESSAGE,
            NotificationPreferenceType::CHAT_REACTION => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Calls: FCM + Database only (real-time)
            NotificationPreferenceType::INCOMING_CALL,
            NotificationPreferenceType::MISSED_CALL,
            NotificationPreferenceType::CALL_ENDED => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Leave/Expense approvals: All channels
            NotificationPreferenceType::LEAVE_APPROVED,
            NotificationPreferenceType::LEAVE_REJECTED,
            NotificationPreferenceType::EXPENSE_APPROVED,
            NotificationPreferenceType::EXPENSE_REJECTED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Payment Collection: New collection (FCM + Database + Email for managers)
            NotificationPreferenceType::PAYMENT_COLLECTION_CREATED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Payment Collection: Verified (FCM + Database)
            NotificationPreferenceType::PAYMENT_VERIFIED => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Payment Collection: Approved (FCM + Database)
            NotificationPreferenceType::PAYMENT_APPROVED => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Payment Collection: Rejected (All channels - important notification)
            NotificationPreferenceType::PAYMENT_REJECTED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Sales Target: Assigned (All channels)
            NotificationPreferenceType::SALES_TARGET_ASSIGNED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Sales Target: Milestone (FCM + Database - celebration)
            NotificationPreferenceType::SALES_TARGET_MILESTONE => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Sales Target: Expiring (All channels - important reminder)
            NotificationPreferenceType::SALES_TARGET_EXPIRING => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Sales Target: Expired (FCM + Database)
            NotificationPreferenceType::SALES_TARGET_EXPIRED => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],

            // Employee Lifecycle: Onboarding (All channels)
            NotificationPreferenceType::EMPLOYEE_ONBOARDING => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Employee Lifecycle: Terminated (All channels - important)
            NotificationPreferenceType::EMPLOYEE_TERMINATED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Employee Lifecycle: Probation Confirmed (All channels)
            NotificationPreferenceType::PROBATION_CONFIRMED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Employee Lifecycle: Probation Failed (All channels - important)
            NotificationPreferenceType::PROBATION_FAILED => [
                'fcm' => true,
                'mail' => true,
                'database' => true,
                'broadcast' => false,
            ],

            // Default: FCM + Database
            default => [
                'fcm' => true,
                'mail' => false,
                'database' => true,
                'broadcast' => false,
            ],
        };
    }
}
