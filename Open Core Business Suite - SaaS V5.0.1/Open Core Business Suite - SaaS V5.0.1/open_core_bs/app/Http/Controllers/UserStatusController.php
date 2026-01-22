<?php

namespace App\Http\Controllers;

use App\Events\UserStatusUpdated;
use App\Models\User;
use App\Models\UserStatusModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserStatusController extends Controller
{
    /**
     * Get all user statuses
     */
    public function index(Request $request)
    {
        try {
            $query = UserStatusModel::with(['user:id,first_name,last_name,code,email'])
                ->active();

            // Filter by user if provided
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $statuses = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $statuses->map(function ($status) {
                    return [
                        'id' => $status->id,
                        'user_id' => $status->user_id,
                        'user' => [
                            'id' => $status->user->id,
                            'name' => $status->user->first_name.' '.$status->user->last_name,
                            'code' => $status->user->code,
                            'email' => $status->user->email,
                        ],
                        'status' => $status->status,
                        'message' => $status->message,
                        'expires_at' => $status->expires_at?->toISOString(),
                        'status_color' => $status->status_color,
                        'status_icon' => $status->status_icon,
                        'updated_at' => $status->updated_at->toISOString(),
                    ];
                }),
            ]);
        } catch (Exception $e) {
            Log::error('Get user statuses error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load user statuses'),
            ], 500);
        }
    }

    /**
     * Get current user's status
     */
    public function me()
    {
        try {
            $status = UserStatusModel::where('user_id', auth()->id())
                ->active()
                ->latest()
                ->first();

            if (! $status) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $status->id,
                    'user_id' => $status->user_id,
                    'status' => $status->status,
                    'message' => $status->message,
                    'expires_at' => $status->expires_at?->toISOString(),
                    'status_color' => $status->status_color,
                    'status_icon' => $status->status_icon,
                    'updated_at' => $status->updated_at->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get current user status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load status'),
            ], 500);
        }
    }

    /**
     * Update or create user status
     */
    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'status' => 'required|string|max:50',
            'message' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            $userId = $request->user_id ?? auth()->id();

            // Get or create user status
            $userStatus = UserStatusModel::firstOrNew(['user_id' => $userId]);
            $userStatus->status = $request->status;
            $userStatus->message = $request->message;
            $userStatus->expires_at = $request->expires_at;
            $userStatus->save();

            DB::commit();

            // Broadcast the status update
            broadcast(new UserStatusUpdated($userStatus));

            return response()->json([
                'success' => true,
                'message' => __('Status updated successfully'),
                'data' => [
                    'id' => $userStatus->id,
                    'user_id' => $userStatus->user_id,
                    'status' => $userStatus->status,
                    'message' => $userStatus->message,
                    'expires_at' => $userStatus->expires_at?->toISOString(),
                    'status_color' => $userStatus->status_color,
                    'status_icon' => $userStatus->status_icon,
                    'updated_at' => $userStatus->updated_at->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update user status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to update status'),
            ], 500);
        }
    }

    /**
     * Get available status options
     */
    public function options()
    {
        $statuses = [
            [
                'value' => 'online',
                'label' => __('Online'),
                'color' => 'success',
                'icon' => 'bx-circle',
            ],
            [
                'value' => 'busy',
                'label' => __('Busy'),
                'color' => 'warning',
                'icon' => 'bx-time',
            ],
            [
                'value' => 'away',
                'label' => __('Away'),
                'color' => 'info',
                'icon' => 'bx-moon',
            ],
            [
                'value' => 'on_call',
                'label' => __('On Call'),
                'color' => 'primary',
                'icon' => 'bx-phone-call',
            ],
            [
                'value' => 'do_not_disturb',
                'label' => __('Do Not Disturb'),
                'color' => 'danger',
                'icon' => 'bx-minus-circle',
            ],
            [
                'value' => 'on_leave',
                'label' => __('On Leave'),
                'color' => 'secondary',
                'icon' => 'bx-calendar-x',
            ],
            [
                'value' => 'on_meeting',
                'label' => __('In Meeting'),
                'color' => 'warning',
                'icon' => 'bx-group',
            ],
            [
                'value' => 'offline',
                'label' => __('Offline'),
                'color' => 'secondary',
                'icon' => 'bx-x-circle',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }

    /**
     * Get user status statistics
     */
    public function statistics()
    {
        try {
            $stats = UserStatusModel::active()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                });

            $total = UserStatusModel::active()->count();
            $totalUsers = User::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'total_users' => $totalUsers,
                    'by_status' => $stats,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get status statistics error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load statistics'),
            ], 500);
        }
    }

    /**
     * Get bulk user statuses by user IDs
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $statuses = UserStatusModel::with(['user:id,first_name,last_name,code'])
                ->whereIn('user_id', $request->user_ids)
                ->active()
                ->get()
                ->keyBy('user_id');

            // Map results with user_id as key
            $result = collect($request->user_ids)->mapWithKeys(function ($userId) use ($statuses) {
                $status = $statuses->get($userId);

                if (! $status) {
                    return [$userId => null];
                }

                return [$userId => [
                    'id' => $status->id,
                    'user_id' => $status->user_id,
                    'user' => [
                        'id' => $status->user->id,
                        'name' => $status->user->first_name.' '.$status->user->last_name,
                        'code' => $status->user->code,
                    ],
                    'status' => $status->status,
                    'message' => $status->message,
                    'expires_at' => $status->expires_at?->toISOString(),
                    'status_color' => $status->status_color,
                    'status_icon' => $status->status_icon,
                    'updated_at' => $status->updated_at->toISOString(),
                ]];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            Log::error('Get bulk user statuses error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load user statuses'),
            ], 500);
        }
    }

    /**
     * Get status for a specific user
     */
    public function show($userId)
    {
        try {
            $status = UserStatusModel::with(['user:id,first_name,last_name,code,email'])
                ->where('user_id', $userId)
                ->active()
                ->latest()
                ->first();

            if (! $status) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $status->id,
                    'user_id' => $status->user_id,
                    'user' => [
                        'id' => $status->user->id,
                        'name' => $status->user->first_name.' '.$status->user->last_name,
                        'code' => $status->user->code,
                        'email' => $status->user->email,
                    ],
                    'status' => $status->status,
                    'message' => $status->message,
                    'expires_at' => $status->expires_at?->toISOString(),
                    'status_color' => $status->status_color,
                    'status_icon' => $status->status_icon,
                    'updated_at' => $status->updated_at->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get user status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load user status'),
            ], 500);
        }
    }

    /**
     * Bulk update user statuses
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.user_id' => 'required|integer|exists:users,id',
            'updates.*.status' => 'required|string|max:50',
            'updates.*.message' => 'nullable|string|max:255',
            'updates.*.expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::beginTransaction();

            $updatedStatuses = [];

            foreach ($request->updates as $update) {
                $userStatus = UserStatusModel::firstOrNew(['user_id' => $update['user_id']]);
                $userStatus->status = $update['status'];
                $userStatus->message = $update['message'] ?? null;
                $userStatus->expires_at = $update['expires_at'] ?? null;
                $userStatus->save();

                // Broadcast each status update
                broadcast(new UserStatusUpdated($userStatus));

                $updatedStatuses[] = [
                    'id' => $userStatus->id,
                    'user_id' => $userStatus->user_id,
                    'status' => $userStatus->status,
                    'message' => $userStatus->message,
                    'expires_at' => $userStatus->expires_at?->toISOString(),
                    'status_color' => $userStatus->status_color,
                    'status_icon' => $userStatus->status_icon,
                    'updated_at' => $userStatus->updated_at->toISOString(),
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Statuses updated successfully'),
                'data' => $updatedStatuses,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk update user statuses error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to update statuses'),
            ], 500);
        }
    }

    /**
     * Clear/reset user status (set to offline)
     */
    public function clear(Request $request)
    {
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
        ]);

        try {
            $userId = $request->user_id ?? auth()->id();

            $userStatus = UserStatusModel::where('user_id', $userId)->first();

            if ($userStatus) {
                $userStatus->status = 'offline';
                $userStatus->message = null;
                $userStatus->expires_at = null;
                $userStatus->save();

                // Broadcast the status update
                broadcast(new UserStatusUpdated($userStatus));
            }

            return response()->json([
                'success' => true,
                'message' => __('Status cleared successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('Clear user status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to clear status'),
            ], 500);
        }
    }

    /**
     * Get users by status
     */
    public function usersByStatus($status)
    {
        try {
            $users = UserStatusModel::with(['user:id,first_name,last_name,code,email'])
                ->where('status', $status)
                ->active()
                ->get()
                ->map(function ($userStatus) {
                    return [
                        'id' => $userStatus->user->id,
                        'name' => $userStatus->user->first_name.' '.$userStatus->user->last_name,
                        'code' => $userStatus->user->code,
                        'email' => $userStatus->user->email,
                        'status' => $userStatus->status,
                        'message' => $userStatus->message,
                        'status_color' => $userStatus->status_color,
                        'status_icon' => $userStatus->status_icon,
                        'updated_at' => $userStatus->updated_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (Exception $e) {
            Log::error('Get users by status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load users'),
            ], 500);
        }
    }
}
