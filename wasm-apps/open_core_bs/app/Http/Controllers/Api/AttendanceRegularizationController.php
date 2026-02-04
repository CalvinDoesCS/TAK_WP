<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRegularization;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceRegularizationController extends Controller
{
    /**
     * Get all regularization requests for authenticated user
     */
    public function getAll(Request $request)
    {
        $skip = $request->skip ?? 0;
        $take = $request->take ?? 10;

        $query = AttendanceRegularization::query()
            ->where('user_id', auth()->id())
            ->with(['approvedBy', 'attendance']);

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('startDate')) {
            try {
                $fromDate = Carbon::createFromFormat('d-m-Y', $request->startDate)->format('Y-m-d');
                $query->whereDate('date', '>=', $fromDate);
            } catch (Exception $e) {
                return Error::response('Invalid startDate format. Expected dd-MM-yyyy');
            }
        }

        if ($request->has('endDate')) {
            try {
                $toDate = Carbon::createFromFormat('d-m-Y', $request->endDate)->format('Y-m-d');
                $query->whereDate('date', '<=', $toDate);
            } catch (Exception $e) {
                return Error::response('Invalid endDate format. Expected dd-MM-yyyy');
            }
        }

        $totalCount = $query->count();

        $regularizations = $query->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($take)
            ->get();

        $finalRegularizations = [];
        foreach ($regularizations as $regularization) {
            // Process attachments - return array of attachment objects
            $attachments = [];
            if ($regularization->attachments) {
                foreach ($regularization->attachments as $attachment) {
                    $attachments[] = [
                        'name' => $attachment['name'] ?? '',
                        'path' => $attachment['url'] ?? Storage::url($attachment['path'] ?? ''),
                        'size' => $attachment['size'] ?? 0,
                        'type' => $attachment['type'] ?? '',
                    ];
                }
            }

            $finalRegularizations[] = [
                'id' => $regularization->id,
                'date' => $regularization->date->format('d-m-Y'),
                'type' => $regularization->type,
                'typeLabel' => $regularization->getTypeLabel(),
                'status' => $regularization->status,
                'statusLabel' => $regularization->getStatusLabel(),
                'requestedCheckInTime' => $regularization->requested_check_in_time ? Carbon::parse($regularization->requested_check_in_time)->format('h:i A') : null,
                'requestedCheckOutTime' => $regularization->requested_check_out_time ? Carbon::parse($regularization->requested_check_out_time)->format('h:i A') : null,
                'actualCheckInTime' => $regularization->actual_check_in_time ? Carbon::parse($regularization->actual_check_in_time)->format('h:i A') : null,
                'actualCheckOutTime' => $regularization->actual_check_out_time ? Carbon::parse($regularization->actual_check_out_time)->format('h:i A') : null,
                'reason' => $regularization->reason,
                'managerComments' => $regularization->manager_comments,
                'approvedBy' => $regularization->approvedBy ? $regularization->approvedBy->getFullName() : null,
                'approvedAt' => $regularization->approved_at ? $regularization->approved_at->format('d-m-Y h:i A') : null,
                'attachments' => $attachments,
                'createdAt' => $regularization->created_at->format('d-m-Y h:i A'),
            ];
        }

        return Success::response([
            'totalCount' => $totalCount,
            'values' => $finalRegularizations,
        ]);
    }

    /**
     * Get a specific regularization request by ID
     */
    public function getById($id)
    {
        $regularization = AttendanceRegularization::with([
            'user',
            'attendance.attendanceLogs',
            'approvedBy',
        ])
            ->where('user_id', auth()->id())
            ->find($id);

        if (! $regularization) {
            return Error::response('Regularization request not found');
        }

        $attendanceLogs = [];
        if ($regularization->attendance) {
            foreach ($regularization->attendance->attendanceLogs as $log) {
                $attendanceLogs[] = [
                    'type' => $log->type,
                    'time' => Carbon::parse($log->created_at)->format('h:i A'),
                    'latitude' => $log->latitude,
                    'longitude' => $log->longitude,
                ];
            }
        }

        // Process attachments - return array of attachment objects
        $attachments = [];
        if ($regularization->attachments) {
            foreach ($regularization->attachments as $attachment) {
                $attachments[] = [
                    'name' => $attachment['name'] ?? '',
                    'path' => $attachment['url'] ?? Storage::url($attachment['path'] ?? ''),
                    'size' => $attachment['size'] ?? 0,
                    'type' => $attachment['type'] ?? '',
                ];
            }
        }

        $data = [
            'id' => $regularization->id,
            'date' => $regularization->date->format('d-m-Y'),
            'type' => $regularization->type,
            'typeLabel' => $regularization->getTypeLabel(),
            'status' => $regularization->status,
            'statusLabel' => $regularization->getStatusLabel(),
            'requestedCheckInTime' => $regularization->requested_check_in_time ? Carbon::parse($regularization->requested_check_in_time)->format('h:i A') : null,
            'requestedCheckOutTime' => $regularization->requested_check_out_time ? Carbon::parse($regularization->requested_check_out_time)->format('h:i A') : null,
            'actualCheckInTime' => $regularization->actual_check_in_time ? Carbon::parse($regularization->actual_check_in_time)->format('h:i A') : null,
            'actualCheckOutTime' => $regularization->actual_check_out_time ? Carbon::parse($regularization->actual_check_out_time)->format('h:i A') : null,
            'reason' => $regularization->reason,
            'managerComments' => $regularization->manager_comments,
            'approvedBy' => $regularization->approvedBy ? $regularization->approvedBy->getFullName() : null,
            'approvedAt' => $regularization->approved_at ? $regularization->approved_at->format('d-m-Y h:i A') : null,
            'attachments' => $attachments,
            'attendanceLogs' => $attendanceLogs,
            'createdAt' => $regularization->created_at->format('d-m-Y h:i A'),
        ];

        return Success::response($data);
    }

    /**
     * Get regularization types
     */
    public function getTypes()
    {
        $types = [
            ['value' => 'missing_checkin', 'label' => __('Missing Check-in')],
            ['value' => 'missing_checkout', 'label' => __('Missing Check-out')],
            ['value' => 'wrong_time', 'label' => __('Wrong Time')],
            ['value' => 'forgot_punch', 'label' => __('Forgot to Punch')],
            ['value' => 'other', 'label' => __('Other')],
        ];

        return Success::response($types);
    }

    /**
     * Create a new regularization request
     */
    public function create(Request $request)
    {
        // Validation
        if (! $request->date) {
            return Error::response('Date is required');
        }

        if (! $request->type) {
            return Error::response('Type is required');
        }

        if (! in_array($request->type, ['missing_checkin', 'missing_checkout', 'wrong_time', 'forgot_punch', 'other'])) {
            return Error::response('Invalid type');
        }

        if (! $request->reason) {
            return Error::response('Reason is required');
        }

        // Validate date format and that it's not in future
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
            if (Carbon::parse($date)->isFuture()) {
                return Error::response('Date cannot be in the future');
            }
        } catch (Exception $e) {
            return Error::response('Invalid date format. Expected dd-MM-yyyy');
        }

        // Validate time format
        if ($request->requestedCheckInTime) {
            try {
                Carbon::createFromFormat('H:i', $request->requestedCheckInTime);
            } catch (Exception $e) {
                return Error::response('Invalid check-in time format. Expected HH:mm');
            }
        }

        if ($request->requestedCheckOutTime) {
            try {
                Carbon::createFromFormat('H:i', $request->requestedCheckOutTime);
            } catch (Exception $e) {
                return Error::response('Invalid check-out time format. Expected HH:mm');
            }
        }

        // Validate check-out time is after check-in time
        if ($request->requestedCheckInTime && $request->requestedCheckOutTime) {
            $checkIn = Carbon::createFromFormat('H:i', $request->requestedCheckInTime);
            $checkOut = Carbon::createFromFormat('H:i', $request->requestedCheckOutTime);
            if ($checkOut->lte($checkIn)) {
                return Error::response('Check-out time must be after check-in time');
            }
        }

        try {
            DB::beginTransaction();

            // Handle file uploads
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Validate file
                    if (! in_array($file->getClientOriginalExtension(), ['pdf', 'jpg', 'jpeg', 'png'])) {
                        DB::rollBack();

                        return Error::response('Invalid file type. Only PDF, JPG, JPEG, PNG are allowed');
                    }

                    if ($file->getSize() > 5120 * 1024) { // 5MB
                        DB::rollBack();

                        return Error::response('File size exceeds 5MB limit');
                    }

                    $path = $file->store('attendance-regularization', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
            }

            // Get existing attendance record if exists
            $attendance = Attendance::where('user_id', auth()->id())
                ->whereDate('created_at', $date)
                ->first();

            AttendanceRegularization::create([
                'user_id' => auth()->id(),
                'attendance_id' => $attendance?->id,
                'date' => $date,
                'type' => $request->type,
                'requested_check_in_time' => $request->requestedCheckInTime,
                'requested_check_out_time' => $request->requestedCheckOutTime,
                'actual_check_in_time' => $attendance?->check_in_time,
                'actual_check_out_time' => $attendance?->check_out_time,
                'reason' => $request->reason,
                'attachments' => $attachments,
                'status' => 'pending',
            ]);

            DB::commit();

            return Success::response('Regularization request submitted successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Attendance regularization creation error: '.$e->getMessage());

            return Error::response('Failed to submit regularization request');
        }
    }

    /**
     * Update an existing regularization request
     * Note: For multipart/form-data, use POST with _method=PUT or use the updatePost endpoint
     */
    public function update(Request $request, $id)
    {
        $regularization = AttendanceRegularization::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->find($id);

        if (! $regularization) {
            return Error::response('Regularization request not found or cannot be edited');
        }

        // Validation
        if (! $request->date) {
            return Error::response('Date is required');
        }

        if (! $request->type) {
            return Error::response('Type is required');
        }

        if (! in_array($request->type, ['missing_checkin', 'missing_checkout', 'wrong_time', 'forgot_punch', 'other'])) {
            return Error::response('Invalid type');
        }

        if (! $request->reason) {
            return Error::response('Reason is required');
        }

        // Validate date format
        try {
            $date = Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
            if (Carbon::parse($date)->isFuture()) {
                return Error::response('Date cannot be in the future');
            }
        } catch (Exception $e) {
            return Error::response('Invalid date format. Expected dd-MM-yyyy');
        }

        // Validate time format
        if ($request->requestedCheckInTime) {
            try {
                Carbon::createFromFormat('H:i', $request->requestedCheckInTime);
            } catch (Exception $e) {
                return Error::response('Invalid check-in time format. Expected HH:mm');
            }
        }

        if ($request->requestedCheckOutTime) {
            try {
                Carbon::createFromFormat('H:i', $request->requestedCheckOutTime);
            } catch (Exception $e) {
                return Error::response('Invalid check-out time format. Expected HH:mm');
            }
        }

        // Validate check-out time is after check-in time
        if ($request->requestedCheckInTime && $request->requestedCheckOutTime) {
            $checkIn = Carbon::createFromFormat('H:i', $request->requestedCheckInTime);
            $checkOut = Carbon::createFromFormat('H:i', $request->requestedCheckOutTime);
            if ($checkOut->lte($checkIn)) {
                return Error::response('Check-out time must be after check-in time');
            }
        }

        try {
            DB::beginTransaction();

            // Handle file uploads
            $attachments = $regularization->attachments ?? [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // Validate file
                    if (! in_array($file->getClientOriginalExtension(), ['pdf', 'jpg', 'jpeg', 'png'])) {
                        DB::rollBack();

                        return Error::response('Invalid file type. Only PDF, JPG, JPEG, PNG are allowed');
                    }

                    if ($file->getSize() > 5120 * 1024) { // 5MB
                        DB::rollBack();

                        return Error::response('File size exceeds 5MB limit');
                    }

                    $path = $file->store('attendance-regularization', 'public');
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                    ];
                }
            }

            $regularization->update([
                'date' => $date,
                'type' => $request->type,
                'requested_check_in_time' => $request->requestedCheckInTime,
                'requested_check_out_time' => $request->requestedCheckOutTime,
                'reason' => $request->reason,
                'attachments' => $attachments,
            ]);

            DB::commit();

            return Success::response('Regularization request updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Attendance regularization update error: '.$e->getMessage());

            return Error::response('Failed to update regularization request');
        }
    }

    /**
     * Delete a regularization request
     */
    public function delete($id)
    {
        $regularization = AttendanceRegularization::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->find($id);

        if (! $regularization) {
            return Error::response('Regularization request not found or cannot be deleted');
        }

        try {
            // Delete uploaded files
            if ($regularization->attachments) {
                foreach ($regularization->attachments as $attachment) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }

            $regularization->delete();

            return Success::response('Regularization request deleted successfully');

        } catch (Exception $e) {
            Log::error('Attendance regularization deletion error: '.$e->getMessage());

            return Error::response('Failed to delete regularization request');
        }
    }

    /**
     * Get count of regularization requests by status
     */
    public function getCounts()
    {
        $pending = AttendanceRegularization::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->count();

        $approved = AttendanceRegularization::where('user_id', auth()->id())
            ->where('status', 'approved')
            ->count();

        $rejected = AttendanceRegularization::where('user_id', auth()->id())
            ->where('status', 'rejected')
            ->count();

        return Success::response([
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $pending + $approved + $rejected,
        ]);
    }

    /**
     * Get available dates that can be regularized
     * Returns dates where user has attendance records or missing attendance
     */
    public function getAvailableDates(Request $request)
    {
        $days = $request->days ?? 30; // Last 30 days by default

        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get all attendance records for the period
        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            });

        // Get all existing regularization requests for the period
        $existingRequests = AttendanceRegularization::where('user_id', auth()->id())
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateKey);
            $hasRequest = $existingRequests->has($dateKey);

            $dates[] = [
                'date' => $currentDate->format('d-m-Y'),
                'hasAttendance' => $attendance !== null,
                'hasCheckIn' => $attendance && $attendance->check_in_time !== null,
                'hasCheckOut' => $attendance && $attendance->check_out_time !== null,
                'hasRegularizationRequest' => $hasRequest,
                'regularizationStatus' => $hasRequest ? $existingRequests->get($dateKey)->status : null,
            ];

            $currentDate->addDay();
        }

        return Success::response([
            'startDate' => $startDate->format('d-m-Y'),
            'endDate' => $endDate->format('d-m-Y'),
            'dates' => $dates,
        ]);
    }
}
