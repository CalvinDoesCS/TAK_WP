<?php

namespace App\Helpers;

use App\Models\FcmToken;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Legacy PushHelper wrapper for FcmNotificationService
 *
 * This class maintains backward compatibility with existing code
 * while using the new FcmNotificationService implementation.
 *
 * @deprecated Use FcmNotificationService directly instead
 */
class PushHelper
{
    protected FcmNotificationService $fcmService;

    public function __construct(?FcmNotificationService $fcmService = null)
    {
        $this->fcmService = $fcmService ?? app(FcmNotificationService::class);
    }

    public function test($data)
    {
        try {
            // Get all users with active FCM tokens
            $userIds = FcmToken::active()
                ->distinct('user_id')
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                $this->fcmService->sendToUser(
                    $userId,
                    [
                        'title' => 'Test Notification',
                        'body' => $data,
                    ],
                    ['type' => 'test'],
                    ['priority' => 'high']
                );
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Test notification failed: '.$e->getMessage());
        }
    }

    public function sendNotificationToUser($userId, $title, $message)
    {
        try {
            Notification::create([
                'from_user_id' => $userId,
                'title' => $title,
                'description' => $message,
                'type' => 'user',
                'created_by_id' => $userId,
            ]);

            $this->fcmService->sendToUser(
                $userId,
                [
                    'title' => $title,
                    'body' => $message,
                ],
                ['type' => 'user'],
                ['priority' => 'high']
            );
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send notification to user: '.$e->getMessage());
        }
    }

    public function sendNotificationToAdmin($title, $message)
    {
        try {
            Notification::create([
                'title' => $title,
                'description' => $message,
                'type' => 'admin',
                'created_by_id' => 1,
            ]);

            // Get admin users (users with no shift_id)
            $adminUsers = User::whereNull('shift_id')->get();

            foreach ($adminUsers as $adminUser) {
                $this->fcmService->sendToUser(
                    $adminUser->id,
                    [
                        'title' => $title,
                        'body' => $message,
                    ],
                    ['type' => 'admin'],
                    ['priority' => 'high']
                );
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send notification to admin: '.$e->getMessage());
        }
    }

    public function sendNotificationForChat($fromUserId, $toUserId, $message)
    {
        try {
            $fromUser = User::find($fromUserId);
            if (! $fromUser) {
                return;
            }

            $title = 'New Message from '.$fromUser->getFullName();

            Notification::create([
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'title' => $title,
                'description' => $message,
                'type' => 'chat',
                'created_by_id' => $fromUserId,
            ]);

            $this->fcmService->sendToUser(
                $toUserId,
                [
                    'title' => $title,
                    'body' => $message,
                ],
                [
                    'type' => 'chat',
                    'from_user_id' => (string) $fromUserId,
                ],
                ['priority' => 'high']
            );
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send chat notification: '.$e->getMessage());
        }
    }

    public function sendNotificationForTeamChat($teamId, $fromUserId, $message, $isExceptUserId = false): void
    {
        try {
            $fromUser = User::find($fromUserId);
            if (! $fromUser) {
                return;
            }

            $title = 'New Message from '.$fromUser->getFullName();

            Notification::create([
                'from_user_id' => $fromUserId,
                'title' => $title,
                'description' => $message,
                'type' => 'chat',
                'created_by_id' => $fromUserId,
            ]);

            if ($isExceptUserId) {
                $this->sendNotificationToTeamExceptUserId($teamId, $fromUserId, $title, $message);
            } else {
                $this->sendNotificationToTeam($teamId, $title, $message);
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send team chat notification: '.$e->getMessage());
        }
    }

    public function sendNotificationToTeam($teamId, $title, $message)
    {
        try {
            $userIds = User::where('team_id', $teamId)->pluck('id');

            foreach ($userIds as $userId) {
                $this->fcmService->sendToUser(
                    $userId,
                    [
                        'title' => $title,
                        'body' => $message,
                    ],
                    [
                        'type' => 'team',
                        'team_id' => (string) $teamId,
                    ],
                    ['priority' => 'high']
                );
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send team notification: '.$e->getMessage());
        }
    }

    public function sendNotificationToTeamExceptUserId($teamId, $userId, $title, $message)
    {
        try {
            $userIds = User::where('team_id', $teamId)
                ->where('id', '!=', $userId)
                ->pluck('id');

            foreach ($userIds as $recipientId) {
                $this->fcmService->sendToUser(
                    $recipientId,
                    [
                        'title' => $title,
                        'body' => $message,
                    ],
                    [
                        'type' => 'team',
                        'team_id' => (string) $teamId,
                    ],
                    ['priority' => 'high']
                );
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send team notification (except user): '.$e->getMessage());
        }
    }

    public function sendNotificationToAll($title, $message)
    {
        try {
            $userIds = FcmToken::active()
                ->distinct('user_id')
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                $this->fcmService->sendToUser(
                    $userId,
                    [
                        'title' => $title,
                        'body' => $message,
                    ],
                    ['type' => 'broadcast'],
                    ['priority' => 'high']
                );
            }
        } catch (\Exception $e) {
            Log::error('[PushHelper] Failed to send notification to all: '.$e->getMessage());
        }
    }
}
