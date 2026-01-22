<?php

namespace App\Services;

use App\Models\FcmToken;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for sending Firebase Cloud Messaging (FCM) push notifications
 *
 * This is a global service used by all applications in the Open Core Business Suite
 * to send push notifications to users' devices.
 *
 * Uses FCM HTTP v1 API with OAuth 2.0 authentication via service account JSON file.
 */
class FcmNotificationService
{
    /**
     * FCM API endpoint
     */
    private const FCM_API_URL = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';

    /**
     * FCM OAuth scope
     */
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Cached access token
     */
    private static ?string $accessToken = null;

    /**
     * Access token expiry time
     */
    private static ?int $tokenExpiry = null;

    /**
     * Get FCM service account credentials path from config
     */
    private function getServiceAccountPath(): string
    {
        $serviceAccountPath = config('services.fcm.service_account_path');

        if (empty($serviceAccountPath)) {
            throw new Exception('FCM service account path not configured. Please set FIREBASE_SERVICE_ACCOUNT_PATH in .env file.');
        }

        if (! file_exists($serviceAccountPath)) {
            throw new Exception("FCM service account file not found at: {$serviceAccountPath}");
        }

        return $serviceAccountPath;
    }

    /**
     * Get FCM project ID from service account file
     */
    private function getProjectId(): string
    {
        $serviceAccountPath = $this->getServiceAccountPath();
        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (! isset($serviceAccount['project_id'])) {
            throw new Exception('Project ID not found in service account file');
        }

        return $serviceAccount['project_id'];
    }

    /**
     * Get OAuth 2.0 access token for FCM API
     *
     * @return string Access token
     *
     * @throws Exception If token generation fails
     */
    private function getAccessToken(): string
    {
        // Return cached token if still valid
        if (self::$accessToken && self::$tokenExpiry && time() < self::$tokenExpiry) {
            return self::$accessToken;
        }

        try {
            $serviceAccountPath = $this->getServiceAccountPath();

            // Create service account credentials
            $credentials = new ServiceAccountCredentials(
                self::FCM_SCOPE,
                $serviceAccountPath
            );

            // Get access token
            $authToken = $credentials->fetchAuthToken();

            if (! isset($authToken['access_token'])) {
                throw new Exception('Failed to get access token from service account');
            }

            // Cache the token (expires in 1 hour, we'll refresh 5 minutes early)
            self::$accessToken = $authToken['access_token'];
            self::$tokenExpiry = time() + 3300; // 55 minutes

            Log::debug('✅ [FCM] Generated new access token');

            return self::$accessToken;
        } catch (Exception $e) {
            Log::error("❌ [FCM] Failed to get access token: {$e->getMessage()}");
            throw new Exception("Failed to authenticate with FCM: {$e->getMessage()}");
        }
    }

    /**
     * Send notification to a specific user (all their active devices)
     *
     * @param  int  $userId  User ID to send notification to
     * @param  array  $notification  Notification content
     * @param  array  $data  Additional data payload
     * @param  array  $options  Additional FCM options (priority, ttl, etc.)
     * @return array Result with success/failure counts and details
     */
    public function sendToUser(
        int $userId,
        array $notification,
        array $data = [],
        array $options = []
    ): array {
        Log::info("📱 [FCM] Sending notification to user {$userId}");

        // Get all active FCM tokens for the user
        $fcmTokens = FcmToken::where('user_id', $userId)
            ->active()
            ->get();

        if ($fcmTokens->isEmpty()) {
            Log::warning("⚠️ [FCM] No active FCM tokens found for user {$userId}");

            return [
                'success' => false,
                'message' => 'No active devices found for user',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        Log::info("📱 [FCM] Found {$fcmTokens->count()} active device(s) for user {$userId}");

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        // Send notification to each device
        foreach ($fcmTokens as $fcmToken) {
            try {
                $this->sendToToken(
                    $fcmToken->fcm_token,
                    $notification,
                    $data,
                    $options
                );

                $fcmToken->markAsUsed();
                $results['sent']++;
                $results['details'][] = [
                    'device_id' => $fcmToken->device_id,
                    'device_type' => $fcmToken->device_type,
                    'success' => true,
                ];

                Log::info("✅ [FCM] Notification sent to device {$fcmToken->device_id} ({$fcmToken->device_type})");
            } catch (Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'device_id' => $fcmToken->device_id,
                    'device_type' => $fcmToken->device_type,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                Log::error("❌ [FCM] Failed to send to device {$fcmToken->device_id}: {$e->getMessage()}");

                // If token is invalid, deactivate it
                if ($this->isInvalidTokenError($e->getMessage())) {
                    Log::warning("🔕 [FCM] Deactivating invalid token for device {$fcmToken->device_id}");
                    $fcmToken->deactivate();
                }
            }
        }

        $results['success'] = $results['sent'] > 0;

        Log::info("📊 [FCM] Notification delivery complete: {$results['sent']} sent, {$results['failed']} failed");

        return $results;
    }

    /**
     * Send notification to specific device types for a user
     *
     * @param  int  $userId  User ID
     * @param  array  $deviceTypes  Array of device types (e.g., ['android', 'ios'])
     * @param  array  $notification  Notification content
     * @param  array  $data  Additional data payload
     * @param  array  $options  Additional FCM options
     * @return array Result with success/failure counts
     */
    public function sendToUserDeviceTypes(
        int $userId,
        array $deviceTypes,
        array $notification,
        array $data = [],
        array $options = []
    ): array {
        Log::info("📱 [FCM] Sending notification to user {$userId} device types: ".implode(', ', $deviceTypes));

        $fcmTokens = FcmToken::where('user_id', $userId)
            ->active()
            ->whereIn('device_type', $deviceTypes)
            ->get();

        if ($fcmTokens->isEmpty()) {
            Log::warning("⚠️ [FCM] No active FCM tokens found for user {$userId} with device types: ".implode(', ', $deviceTypes));

            return [
                'success' => false,
                'message' => 'No active devices found',
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($fcmTokens as $fcmToken) {
            try {
                $this->sendToToken(
                    $fcmToken->fcm_token,
                    $notification,
                    $data,
                    $options
                );

                $fcmToken->markAsUsed();
                $results['sent']++;
                $results['details'][] = [
                    'device_id' => $fcmToken->device_id,
                    'device_type' => $fcmToken->device_type,
                    'success' => true,
                ];
            } catch (Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'device_id' => $fcmToken->device_id,
                    'device_type' => $fcmToken->device_type,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                if ($this->isInvalidTokenError($e->getMessage())) {
                    $fcmToken->deactivate();
                }
            }
        }

        $results['success'] = $results['sent'] > 0;

        return $results;
    }

    /**
     * Send notification to a specific FCM token
     *
     * @param  string  $token  FCM token
     * @param  array  $notification  Notification content (title, body, image, etc.)
     * @param  array  $data  Additional data payload
     * @param  array  $options  Additional FCM options (priority, ttl, etc.)
     * @return bool Success status
     *
     * @throws Exception If sending fails
     */
    public function sendToToken(
        string $token,
        array $notification,
        array $data = [],
        array $options = []
    ): bool {
        try {
            // Get OAuth access token
            $accessToken = $this->getAccessToken();
            $projectId = $this->getProjectId();

            // Build FCM message payload
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => $notification,
                    'data' => $this->convertDataToStrings($data),
                ],
            ];

            // Add Android-specific configuration
            if (isset($options['android'])) {
                $message['message']['android'] = $options['android'];
            } else {
                $message['message']['android'] = [
                    'priority' => $options['priority'] ?? 'high',
                    'notification' => [
                        'channel_id' => $data['type'] ?? 'default',
                        'sound' => $options['sound'] ?? 'default',
                    ],
                ];
            }

            // Add iOS-specific configuration
            if (isset($options['apns'])) {
                $message['message']['apns'] = $options['apns'];
            } else {
                $message['message']['apns'] = [
                    'headers' => [
                        'apns-priority' => $options['priority'] === 'high' ? '10' : '5',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => $options['sound'] ?? 'default',
                            'badge' => $options['badge'] ?? 1,
                        ],
                    ],
                ];
            }

            // Add Web-specific configuration (if needed)
            if (isset($options['webpush'])) {
                $message['message']['webpush'] = $options['webpush'];
            }

            // Build API URL
            $url = str_replace('{project_id}', $projectId, self::FCM_API_URL);

            // Send HTTP request to FCM
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if (! $response->successful()) {
                $errorMessage = $response->json('error.message') ?? $response->body();
                throw new Exception("FCM API Error: {$errorMessage}");
            }

            Log::debug('✅ [FCM] Successfully sent notification', [
                'response' => $response->json(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("❌ [FCM] Failed to send notification: {$e->getMessage()}", [
                'token' => substr($token, 0, 20).'...',
                'notification' => $notification,
            ]);

            throw $e;
        }
    }

    /**
     * Send incoming call notification to user
     *
     * @param  int  $userId  User ID receiving the call
     * @param  array  $callData  Call information (includes caller details)
     * @return array Result with success/failure counts
     */
    public function sendIncomingCallNotification(int $userId, array $callData): array
    {
        Log::info("📞 [FCM] Sending incoming call notification to user {$userId}");

        // Build notification title and body
        $callerName = $callData['caller_name'] ?? 'Unknown';
        $callType = $callData['call_type'] ?? 'audio';
        $isVideoCall = $callType === 'video';

        // Build notification body with caller details
        $bodyParts = [$callerName];
        if (! empty($callData['caller_designation'])) {
            $bodyParts[] = $callData['caller_designation'];
        }
        if (! empty($callData['caller_department'])) {
            $bodyParts[] = $callData['caller_department'];
        }

        $notification = [
            'title' => $isVideoCall ? 'Incoming Video Call' : 'Incoming Voice Call',
            'body' => implode(' • ', $bodyParts),
        ];

        // Build data payload with all caller information
        $data = [
            'type' => 'incoming_call',
            'call_id' => (string) $callData['call_id'],
            'channel_id' => (string) $callData['channel_id'],
            'caller_id' => (string) $callData['caller_id'],
            'caller_name' => $callerName,
            'caller_designation' => $callData['caller_designation'] ?? '',
            'caller_department' => $callData['caller_department'] ?? '',
            'call_type' => $callType,
        ];

        // Configure high-priority options for incoming calls
        $options = [
            'priority' => 'high',
            'sound' => 'ringtone', // Uses ringtone.mp3 in Android res/raw/
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'incoming_calls',
                    'sound' => 'ringtone', // Android notification sound
                    'tag' => 'call_'.$callData['call_id'],
                    'default_sound' => false,
                    'default_vibrate_timings' => false,
                    'default_light_settings' => false,
                    'notification_priority' => 'PRIORITY_MAX',
                    'visibility' => 'PUBLIC', // Show on lock screen
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10', // High priority for iOS
                    'apns-push-type' => 'alert',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'ringtone.mp3', // iOS notification sound
                        'badge' => 1,
                        'category' => 'incoming_call',
                        'interruption-level' => 'critical', // Break through Do Not Disturb
                        'alert' => [
                            'title' => $notification['title'],
                            'body' => $notification['body'],
                        ],
                    ],
                ],
            ],
        ];

        Log::info('📞 [FCM] Call notification details', [
            'caller' => $callerName,
            'designation' => $data['caller_designation'],
            'department' => $data['caller_department'],
            'type' => $callType,
        ]);

        return $this->sendToUser($userId, $notification, $data, $options);
    }

    /**
     * Convert data array values to strings (FCM requirement)
     *
     * @param  array  $data  Data array
     * @return array Data array with string values
     */
    private function convertDataToStrings(array $data): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $converted[$key] = json_encode($value);
            } else {
                $converted[$key] = (string) $value;
            }
        }

        return $converted;
    }

    /**
     * Check if error message indicates an invalid token
     *
     * @param  string  $errorMessage  Error message from FCM
     * @return bool True if token is invalid
     */
    private function isInvalidTokenError(string $errorMessage): bool
    {
        $invalidTokenPatterns = [
            'InvalidRegistration',
            'NotRegistered',
            'MismatchSenderId',
            'InvalidApnsCredential',
            'Unregistered',
            'INVALID_ARGUMENT',
            'NOT_FOUND',
        ];

        foreach ($invalidTokenPatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
