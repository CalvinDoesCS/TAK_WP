<?php

namespace App\Channels;

use App\Services\FcmNotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Custom FCM (Firebase Cloud Messaging) notification channel
 *
 * Integrates with Laravel's notification system to send push notifications
 * via Firebase Cloud Messaging to all user's registered devices.
 *
 * Usage in notification class:
 * public function via($notifiable) {
 *     return ['fcm', 'database', 'mail'];
 * }
 *
 * public function toFcm($notifiable) {
 *     return [
 *         'title' => 'Notification Title',
 *         'body' => 'Notification body text',
 *         'data' => ['type' => 'notification_type', 'id' => '123'],
 *         'options' => ['priority' => 'high', 'sound' => 'default'],
 *     ];
 * }
 */
class FcmChannel
{
    /**
     * FCM notification service
     */
    protected FcmNotificationService $fcmService;

    /**
     * Create a new FCM channel instance
     */
    public function __construct(FcmNotificationService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification via FCM
     *
     * @param  mixed  $notifiable  The entity receiving the notification (usually User model)
     * @param  Notification  $notification  The notification instance
     */
    public function send($notifiable, Notification $notification): void
    {
        try {
            // Check if notification has toFcm method
            if (! method_exists($notification, 'toFcm')) {
                Log::warning('[FcmChannel] Notification does not have toFcm method', [
                    'notification' => get_class($notification),
                    'notifiable' => get_class($notifiable),
                ]);

                return;
            }

            // Get FCM notification data from the notification
            $fcmData = $notification->toFcm($notifiable);

            // Validate required fields
            if (! isset($fcmData['title']) || ! isset($fcmData['body'])) {
                Log::error('[FcmChannel] FCM notification missing required fields (title or body)', [
                    'notification' => get_class($notification),
                    'data' => $fcmData,
                ]);

                return;
            }

            // Extract notification components
            $notificationPayload = [
                'title' => $fcmData['title'],
                'body' => $fcmData['body'],
            ];

            // Add optional image if provided
            if (isset($fcmData['image'])) {
                $notificationPayload['image'] = $fcmData['image'];
            }

            // Extract data payload (default to empty array)
            $dataPayload = $fcmData['data'] ?? [];

            // Extract options (priority, sound, etc.)
            $options = $fcmData['options'] ?? [];

            // Get user ID from notifiable
            $userId = $notifiable->id ?? $notifiable->getKey();

            Log::info('[FcmChannel] Sending FCM notification', [
                'notification' => get_class($notification),
                'user_id' => $userId,
                'title' => $notificationPayload['title'],
            ]);

            // Send notification to all user's devices
            $result = $this->fcmService->sendToUser(
                $userId,
                $notificationPayload,
                $dataPayload,
                $options
            );

            // Log result
            if ($result['success']) {
                Log::info('[FcmChannel] FCM notification sent successfully', [
                    'notification' => get_class($notification),
                    'user_id' => $userId,
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                ]);
            } else {
                Log::warning('[FcmChannel] FCM notification failed', [
                    'notification' => get_class($notification),
                    'user_id' => $userId,
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to fail the entire notification
            // if just FCM fails (other channels like database/email should still work)
            Log::error('[FcmChannel] Exception while sending FCM notification', [
                'notification' => get_class($notification),
                'notifiable' => get_class($notifiable),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
