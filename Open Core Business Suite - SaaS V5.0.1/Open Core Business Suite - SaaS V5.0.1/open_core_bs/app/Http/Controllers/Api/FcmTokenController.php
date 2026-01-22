<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global FCM Token Controller
 *
 * This controller manages Firebase Cloud Messaging tokens for all applications.
 * It can be used by any app (Employee Mobile, Open Core Connect, Manager App, etc.)
 */
class FcmTokenController extends Controller
{
    /**
     * Register or update FCM token for a device
     */
    public function registerToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|max:255',
            'device_type' => 'required|in:android,ios,web,macos,windows,linux',
            'fcm_token' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        try {
            $userId = auth()->id();

            // Update or create FCM token record
            $fcmToken = FcmToken::updateOrCreate(
                [
                    'user_id' => $userId,
                    'device_id' => $request->device_id,
                ],
                [
                    'device_type' => $request->device_type,
                    'device_name' => $request->device_name,
                    'fcm_token' => $request->fcm_token,
                    'app_version' => $request->app_version,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );

            return ApiResponse::success([
                'id' => $fcmToken->id,
                'message' => 'FCM token registered successfully',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all registered tokens for authenticated user
     */
    public function getUserTokens(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $tokens = FcmToken::forUser($userId)
                ->active()
                ->select(['id', 'device_id', 'device_type', 'device_name', 'app_version', 'is_active', 'last_used_at', 'created_at'])
                ->orderBy('last_used_at', 'desc')
                ->get();

            return ApiResponse::success([
                'tokens' => $tokens,
                'total' => $tokens->count(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Deactivate/unregister a specific FCM token
     */
    public function deactivateToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        try {
            $userId = auth()->id();

            $token = FcmToken::where('user_id', $userId)
                ->where('device_id', $request->device_id)
                ->firstOrFail();

            $token->deactivate();

            return ApiResponse::success([
                'message' => 'FCM token deactivated successfully',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a specific FCM token
     */
    public function deleteToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        try {
            $userId = auth()->id();

            $deleted = FcmToken::where('user_id', $userId)
                ->where('device_id', $request->device_id)
                ->delete();

            if ($deleted) {
                return ApiResponse::success([
                    'message' => 'FCM token deleted successfully',
                ]);
            }

            return ApiResponse::error('Token not found', 404);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Refresh/update FCM token for a device
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        try {
            $userId = auth()->id();

            $token = FcmToken::where('user_id', $userId)
                ->where('device_id', $request->device_id)
                ->firstOrFail();

            $token->update([
                'fcm_token' => $request->fcm_token,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return ApiResponse::success([
                'message' => 'FCM token refreshed successfully',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Deactivate all tokens for current user (logout from all devices)
     */
    public function deactivateAllTokens(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $updated = FcmToken::where('user_id', $userId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return ApiResponse::success([
                'message' => 'All FCM tokens deactivated successfully',
                'count' => $updated,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
