<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Enums\NotificationPreferenceType;
use App\Helpers\NotificationPreferenceHelper;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    /**
     * Get user's notification preferences
     *
     * Returns all notification preferences for the authenticated user.
     * If a preference doesn't exist for a notification type, returns the default.
     *
     * GET /api/V1/notifications/preferences
     */
    public function index()
    {
        try {
            $userId = auth()->id();

            // Get all user's preferences from database
            $userPreferences = NotificationPreference::where('user_id', $userId)->get();

            // Build response with all notification types
            $preferences = [];

            foreach (NotificationPreferenceType::cases() as $type) {
                // Find existing preference for this type
                $preference = $userPreferences->firstWhere('notification_type', $type->value);

                if ($preference) {
                    // User has set preferences for this type
                    $preferences[] = [
                        'notification_type' => $type->value,
                        'notification_type_label' => $this->getNotificationTypeLabel($type),
                        'fcm_enabled' => $preference->fcm_enabled,
                        'mail_enabled' => $preference->mail_enabled,
                        'database_enabled' => $preference->database_enabled,
                        'broadcast_enabled' => $preference->broadcast_enabled,
                    ];
                } else {
                    // Use defaults for this notification type
                    $defaults = NotificationPreferenceHelper::getDefaultChannels($type);

                    $preferences[] = [
                        'notification_type' => $type->value,
                        'notification_type_label' => $this->getNotificationTypeLabel($type),
                        'fcm_enabled' => $defaults['fcm'] ?? true,
                        'mail_enabled' => $defaults['mail'] ?? false,
                        'database_enabled' => $defaults['database'] ?? true,
                        'broadcast_enabled' => $defaults['broadcast'] ?? false,
                        'is_default' => true, // Indicates this is using defaults
                    ];
                }
            }

            return Success::response([
                'preferences' => $preferences,
                'total' => count($preferences),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notification preferences: '.$e->getMessage());

            return Error::response('Failed to fetch notification preferences', 500);
        }
    }

    /**
     * Update user's notification preferences
     *
     * Updates or creates notification preferences for specific notification types.
     * Can update single or multiple notification types in one request.
     *
     * PUT /api/V1/notifications/preferences
     *
     * Request body:
     * {
     *   "preferences": [
     *     {
     *       "notification_type": "chat_message",
     *       "fcm_enabled": true,
     *       "mail_enabled": false,
     *       "database_enabled": true,
     *       "broadcast_enabled": false
     *     }
     *   ]
     * }
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array|min:1',
            'preferences.*.notification_type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, NotificationPreferenceType::cases())),
            ],
            'preferences.*.fcm_enabled' => 'required|boolean',
            'preferences.*.mail_enabled' => 'required|boolean',
            'preferences.*.database_enabled' => 'required|boolean',
            'preferences.*.broadcast_enabled' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $userId = auth()->id();
            $updated = [];

            foreach ($validated['preferences'] as $preferenceData) {
                // Update or create preference
                $preference = NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'notification_type' => $preferenceData['notification_type'],
                    ],
                    [
                        'fcm_enabled' => $preferenceData['fcm_enabled'],
                        'mail_enabled' => $preferenceData['mail_enabled'],
                        'database_enabled' => $preferenceData['database_enabled'],
                        'broadcast_enabled' => $preferenceData['broadcast_enabled'],
                    ]
                );

                $updated[] = [
                    'notification_type' => $preference->notification_type,
                    'notification_type_label' => $this->getNotificationTypeLabel(
                        NotificationPreferenceType::from($preference->notification_type)
                    ),
                    'fcm_enabled' => $preference->fcm_enabled,
                    'mail_enabled' => $preference->mail_enabled,
                    'database_enabled' => $preference->database_enabled,
                    'broadcast_enabled' => $preference->broadcast_enabled,
                ];
            }

            DB::commit();

            return Success::response([
                'message' => 'Notification preferences updated successfully',
                'updated' => $updated,
                'count' => count($updated),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update notification preferences: '.$e->getMessage());

            return Error::response('Failed to update notification preferences', 500);
        }
    }

    /**
     * Reset user's notification preferences to defaults
     *
     * DELETE /api/V1/notifications/preferences
     *
     * Optional query parameter:
     * ?notification_type=chat_message - Reset specific type only
     */
    public function destroy(Request $request)
    {
        try {
            $userId = auth()->id();

            if ($request->has('notification_type')) {
                // Reset specific notification type
                $validated = $request->validate([
                    'notification_type' => [
                        'required',
                        'string',
                        Rule::in(array_map(fn ($case) => $case->value, NotificationPreferenceType::cases())),
                    ],
                ]);

                NotificationPreference::where('user_id', $userId)
                    ->where('notification_type', $validated['notification_type'])
                    ->delete();

                return Success::response([
                    'message' => 'Notification preference reset to default',
                    'notification_type' => $validated['notification_type'],
                ]);
            } else {
                // Reset all preferences
                $deleted = NotificationPreference::where('user_id', $userId)->delete();

                return Success::response([
                    'message' => 'All notification preferences reset to defaults',
                    'deleted_count' => $deleted,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to reset notification preferences: '.$e->getMessage());

            return Error::response('Failed to reset notification preferences', 500);
        }
    }

    /**
     * Get human-readable label for notification type
     */
    private function getNotificationTypeLabel(NotificationPreferenceType $type): string
    {
        return match ($type) {
            NotificationPreferenceType::CHAT_MESSAGE => 'Chat Message',
            NotificationPreferenceType::CHAT_REACTION => 'Chat Reaction',
            NotificationPreferenceType::INCOMING_CALL => 'Incoming Call',
            NotificationPreferenceType::MISSED_CALL => 'Missed Call',
            NotificationPreferenceType::CALL_ENDED => 'Call Ended',
            NotificationPreferenceType::LEAVE_REQUEST => 'Leave Request',
            NotificationPreferenceType::LEAVE_APPROVED => 'Leave Approved',
            NotificationPreferenceType::LEAVE_REJECTED => 'Leave Rejected',
            NotificationPreferenceType::EXPENSE_APPROVED => 'Expense Approved',
            NotificationPreferenceType::EXPENSE_REJECTED => 'Expense Rejected',
            NotificationPreferenceType::LOAN_APPROVED => 'Loan Approved',
            NotificationPreferenceType::LOAN_REJECTED => 'Loan Rejected',
            default => ucwords(str_replace('_', ' ', $type->value)),
        };
    }
}
