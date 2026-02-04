<?php

namespace App\Observers;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "updating" event.
     * This runs before the model is saved, allowing us to detect status changes.
     */
    public function updating(LeaveRequest $leaveRequest): void
    {
        // Check if status is changing to CANCELLED or CANCELLED_BY_ADMIN
        if ($leaveRequest->isDirty('status')) {
            $newStatus = $leaveRequest->status;
            $oldStatus = $leaveRequest->getOriginal('status');

            $cancelledStatuses = [
                LeaveRequestStatus::CANCELLED,
                LeaveRequestStatus::CANCELLED_BY_ADMIN,
            ];

            // If changing to a cancelled status and has Comp Offs
            if (in_array($newStatus, $cancelledStatuses) && ! in_array($oldStatus, $cancelledStatuses)) {
                // Check use_comp_off flag and comp_off_ids more safely
                // During updating hook, comp_off_ids might still be a JSON string
                $compOffIds = $leaveRequest->comp_off_ids;
                $hasCompOffs = false;

                if (is_array($compOffIds)) {
                    $hasCompOffs = ! empty($compOffIds);
                } elseif (is_string($compOffIds)) {
                    $decoded = json_decode($compOffIds, true);
                    $hasCompOffs = ! empty($decoded) && is_array($decoded);
                }

                if ($leaveRequest->use_comp_off && $hasCompOffs) {
                    try {
                        $leaveRequest->releaseCompOffs();
                        Log::info("Observer: Released Comp Offs for leave request {$leaveRequest->id}");
                    } catch (\Exception $e) {
                        Log::error("Observer: Failed to release Comp Offs for leave request {$leaveRequest->id}: {$e->getMessage()}");
                    }
                }
            }
        }
    }

    /**
     * Handle the LeaveRequest "deleted" event.
     * Release Comp Offs if a leave request is deleted.
     */
    public function deleted(LeaveRequest $leaveRequest): void
    {
        // Check use_comp_off flag and comp_off_ids more safely
        $compOffIds = $leaveRequest->comp_off_ids;
        $hasCompOffs = false;

        if (is_array($compOffIds)) {
            $hasCompOffs = ! empty($compOffIds);
        } elseif (is_string($compOffIds)) {
            $decoded = json_decode($compOffIds, true);
            $hasCompOffs = ! empty($decoded) && is_array($decoded);
        }

        if ($leaveRequest->use_comp_off && $hasCompOffs) {
            try {
                $leaveRequest->releaseCompOffs();
                Log::info("Observer: Released Comp Offs for deleted leave request {$leaveRequest->id}");
            } catch (\Exception $e) {
                Log::error("Observer: Failed to release Comp Offs for deleted leave request {$leaveRequest->id}: {$e->getMessage()}");
            }
        }
    }
}
