<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Enums\LeaveRequestStatus;
use App\Enums\Status;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\CompensatoryOff;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserAvailableLeave;
use App\Notifications\Leave\CancelLeaveRequest;
use App\Notifications\Leave\NewLeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    /**
     * Get all active leave types with detailed information
     *
     * GET /api/V1/leave/types
     */
    public function getLeaveTypes()
    {
        $currentYear = Carbon::now()->year;
        $userId = auth()->id();

        $leaveTypes = LeaveType::where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get();

        $response = $leaveTypes->map(function ($leaveType) use ($userId, $currentYear) {
            // Get user's balance for this leave type
            $balance = UserAvailableLeave::where('user_id', $userId)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $currentYear)
                ->first();

            return [
                'id' => $leaveType->id,
                'name' => $leaveType->name,
                'code' => $leaveType->code,
                'notes' => $leaveType->notes,
                'isProofRequired' => $leaveType->is_proof_required,
                'isCompOffType' => $leaveType->is_comp_off_type,
                'allowCarryForward' => $leaveType->allow_carry_forward,
                'maxCarryForward' => $leaveType->max_carry_forward,
                'carryForwardExpiryMonths' => $leaveType->carry_forward_expiry_months,
                'allowEncashment' => $leaveType->allow_encashment,
                'maxEncashmentDays' => $leaveType->max_encashment_days,
                'balance' => [
                    'entitled' => $balance->entitled_leaves ?? 0,
                    'carriedForward' => $balance->carried_forward_leaves ?? 0,
                    'additional' => $balance->additional_leaves ?? 0,
                    'used' => $balance->used_leaves ?? 0,
                    'available' => $balance->available_leaves ?? 0,
                ],
            ];
        });

        return Success::response($response);
    }

    /**
     * Get leave balance summary for current user
     *
     * GET /api/V1/leave/balance
     */
    public function getLeaveBalance(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = auth()->id();

        $balances = UserAvailableLeave::where('user_id', $userId)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        $response = $balances->map(function ($balance) use ($year) {
            // Get pending leaves for this leave type
            $pendingLeaves = LeaveRequest::where('user_id', $balance->user_id)
                ->where('leave_type_id', $balance->leave_type_id)
                ->where('status', LeaveRequestStatus::PENDING)
                ->whereYear('from_date', $year)
                ->sum('total_days');

            return [
                'leaveType' => [
                    'id' => $balance->leaveType->id,
                    'name' => $balance->leaveType->name,
                    'code' => $balance->leaveType->code,
                ],
                'year' => $balance->year,
                'entitled' => $balance->entitled_leaves,
                'carriedForward' => $balance->carried_forward_leaves,
                'carryForwardExpiry' => $balance->carry_forward_expiry_date?->format(Constants::DateFormat),
                'additional' => $balance->additional_leaves,
                'used' => $balance->used_leaves,
                'pending' => $pendingLeaves,
                'available' => $balance->available_leaves,
                'total' => $balance->entitled_leaves + $balance->carried_forward_leaves + $balance->additional_leaves,
            ];
        });

        // Add compensatory off balance
        $compOffBalance = CompensatoryOff::getAvailableBalance($userId);

        return Success::response([
            'year' => $year,
            'leaveBalances' => $response,
            'compensatoryOffBalance' => $compOffBalance,
        ]);
    }

    /**
     * Get paginated list of leave requests for current user
     *
     * GET /api/V1/leave/requests
     * Query params: skip, take, status, year, leaveTypeId
     */
    public function getLeaveRequests(Request $request)
    {
        $skip = $request->input('skip', 0);
        $take = $request->input('take', 20);

        $query = LeaveRequest::query()
            ->where('user_id', auth()->id())
            ->with(['leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])
            ->orderBy('from_date', 'desc');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', LeaveRequestStatus::from($request->status));
        }

        if ($request->has('year')) {
            $query->whereYear('from_date', $request->year);
        }

        if ($request->has('leaveTypeId')) {
            $query->where('leave_type_id', $request->leaveTypeId);
        }

        $totalCount = $query->count();
        $leaveRequests = $query->skip($skip)->take($take)->get();

        $values = $leaveRequests->map(function ($leave) {
            return $this->formatLeaveRequest($leave, false);
        });

        return Success::response([
            'totalCount' => $totalCount,
            'values' => $values,
        ]);
    }

    /**
     * Get single leave request details
     *
     * GET /api/V1/leave/requests/{id}
     */
    public function getLeaveRequest(int $id)
    {
        $leaveRequest = LeaveRequest::with(['leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])
            ->where('user_id', auth()->id())
            ->find($id);

        if (! $leaveRequest) {
            return Error::response('Leave request not found', 404);
        }

        return Success::response($this->formatLeaveRequest($leaveRequest, true));
    }

    /**
     * Create a new leave request
     *
     * POST /api/V1/leave/requests
     */
    public function createLeaveRequest(Request $request)
    {
        // First, get leave type to check if proof is required
        $leaveType = LeaveType::find($request->leaveTypeId);

        $validationRules = [
            'leaveTypeId' => 'required|exists:leave_types,id',
            'fromDate' => 'required|date|after_or_equal:today',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'userNotes' => 'required|string|max:500',
            'isHalfDay' => 'nullable|boolean',
            'halfDayType' => 'required_if:isHalfDay,true|in:first_half,second_half',
            'emergencyContact' => 'nullable|string|max:100',
            'emergencyPhone' => 'nullable|string|max:50',
            'isAbroad' => 'nullable|boolean',
            'abroadLocation' => 'nullable|required_if:isAbroad,true|string|max:200',
            'useCompOff' => 'nullable|boolean',
            'compOffIds' => 'nullable|array',
            'compOffIds.*' => 'nullable|exists:compensatory_offs,id',
        ];

        $validationMessages = [
            'leaveTypeId.required' => 'Leave type is required',
            'leaveTypeId.exists' => 'Invalid leave type selected',
            'fromDate.required' => 'From date is required',
            'fromDate.after_or_equal' => 'From date must be today or later',
            'toDate.required' => 'To date is required',
            'toDate.after_or_equal' => 'To date must be on or after from date',
            'userNotes.required' => 'Reason for leave is required',
            'halfDayType.required_if' => 'Half day type is required for half-day leave',
            'abroadLocation.required_if' => 'Location is required when traveling abroad',
        ];

        // Add document validation if proof is required
        if ($leaveType && $leaveType->is_proof_required) {
            $validationRules['document'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $validationMessages['document.required'] = 'Supporting document is required for this leave type';
            $validationMessages['document.mimes'] = 'Only PDF, JPG, and PNG files are allowed';
            $validationMessages['document.max'] = 'File size must not exceed 5MB';
        } else {
            $validationRules['document'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $validationMessages['document.mimes'] = 'Only PDF, JPG, and PNG files are allowed';
            $validationMessages['document.max'] = 'File size must not exceed 5MB';
        }

        $validator = Validator::make($request->all(), $validationRules, $validationMessages);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $fromDate = Carbon::parse($request->fromDate);
            $toDate = Carbon::parse($request->toDate);
            $isHalfDay = $request->boolean('isHalfDay');
            $currentYear = Carbon::now()->year;
            $userId = auth()->id();

            // Validate half-day constraint
            if ($isHalfDay && ! $fromDate->isSameDay($toDate)) {
                return Error::response('Half-day leave must be for a single date', 422);
            }

            // Calculate total working days for the leave period
            $totalDays = $this->calculateLeaveDays($fromDate, $toDate, $isHalfDay);

            // Handle compensatory off usage first
            $compOffDaysUsed = 0;
            $useCompOff = $request->boolean('useCompOff');

            // Get comp off IDs - handle both array and individual keys (compOffIds[0], compOffIds[1], etc.)
            $compOffIds = $request->input('compOffIds', []);
            if (empty($compOffIds) && $useCompOff) {
                // Try to get individual array elements sent as separate form fields
                $compOffIds = [];
                $index = 0;
                while ($request->has("compOffIds.{$index}")) {
                    $compOffIds[] = $request->input("compOffIds.{$index}");
                    $index++;
                }
            }

            if ($useCompOff && ! empty($compOffIds)) {

                // Verify comp offs belong to user and are available
                $compOffs = CompensatoryOff::whereIn('id', $compOffIds)
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->where('is_used', false)
                    ->where('expiry_date', '>=', now()->toDateString())
                    ->get();

                if ($compOffs->count() !== count($compOffIds)) {
                    return Error::response('One or more compensatory offs are invalid or unavailable', 422);
                }

                $compOffDaysUsed = $compOffs->sum('comp_off_days');

                // Validate comp off days don't exceed the total working days
                if ($compOffDaysUsed > $totalDays) {
                    return Error::response('Compensatory off days ('.$compOffDaysUsed.') cannot exceed the total leave days requested ('.$totalDays.')', 422);
                }
            }

            // Calculate days needed from leave balance after comp off
            $daysNeededFromLeaveBalance = max(0, $totalDays - $compOffDaysUsed);

            // Check leave balance only if days are needed from leave balance
            if ($daysNeededFromLeaveBalance > 0) {
                $balance = UserAvailableLeave::where('user_id', $userId)
                    ->where('leave_type_id', $request->leaveTypeId)
                    ->where('year', $currentYear)
                    ->first();

                if ($balance && $balance->available_leaves < $daysNeededFromLeaveBalance) {
                    return Error::response(
                        'Insufficient leave balance. You need '.$daysNeededFromLeaveBalance.' days but have '.$balance->available_leaves.' days available.',
                        422
                    );
                }
            }

            // Check for overlapping leaves
            $hasOverlap = LeaveRequest::where('user_id', $userId)
                ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->whereBetween('from_date', [$fromDate, $toDate])
                        ->orWhereBetween('to_date', [$fromDate, $toDate])
                        ->orWhere(function ($q) use ($fromDate, $toDate) {
                            $q->where('from_date', '<=', $fromDate)
                                ->where('to_date', '>=', $toDate);
                        });
                })
                ->exists();

            if ($hasOverlap) {
                return Error::response('You already have a leave request for the selected dates', 422);
            }

            // Handle document upload if provided
            $documentFileName = null;
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
                Storage::disk('public')->putFileAs(Constants::BaseFolderLeaveRequestDocument, $file, $fileName);
                $documentFileName = Constants::BaseFolderLeaveRequestDocument.$fileName;
            }

            // Create leave request
            $leaveRequest = LeaveRequest::create([
                'user_id' => $userId,
                'leave_type_id' => $request->leaveTypeId,
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
                'is_half_day' => $isHalfDay,
                'half_day_type' => $isHalfDay ? $request->halfDayType : null,
                'total_days' => $totalDays,
                'user_notes' => $request->userNotes,
                'emergency_contact' => $request->emergencyContact,
                'emergency_phone' => $request->emergencyPhone,
                'is_abroad' => $request->boolean('isAbroad'),
                'abroad_location' => $request->boolean('isAbroad') ? $request->abroadLocation : null,
                'document' => $documentFileName,
                'status' => LeaveRequestStatus::PENDING,
                'use_comp_off' => $useCompOff && ! empty($compOffIds),
                'comp_off_days_used' => $compOffDaysUsed,
                'comp_off_ids' => ! empty($compOffIds) ? json_encode($compOffIds) : null,
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ]);

            // Mark compensatory offs as used
            if ($useCompOff && ! empty($compOffIds)) {
                CompensatoryOff::whereIn('id', $compOffIds)
                    ->update([
                        'is_used' => true,
                        'used_date' => now(),
                        'leave_request_id' => $leaveRequest->id,
                        'updated_by_id' => $userId,
                    ]);
            }

            DB::commit();

            // Send notification
            NotificationHelper::notifyAdminHR(new NewLeaveRequest($leaveRequest));

            return Success::response([
                'message' => 'Leave request submitted successfully',
                'leaveRequestId' => $leaveRequest->id,
                'leaveRequest' => $this->formatLeaveRequest($leaveRequest->fresh(['leaveType']), true),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded file if exists
            if (isset($documentFileName) && $documentFileName) {
                Storage::disk('public')->delete($documentFileName);
            }

            Log::error('Failed to create leave request: '.$e->getMessage());

            return Error::response('Failed to create leave request. Please try again.', 500);
        }
    }

    /**
     * Update a pending leave request
     *
     * PUT /api/V1/leave/requests/{id}
     */
    public function updateLeaveRequest(Request $request, int $id)
    {
        $leaveRequest = LeaveRequest::where('user_id', auth()->id())->find($id);

        if (! $leaveRequest) {
            return Error::response('Leave request not found', 404);
        }

        if ($leaveRequest->status !== LeaveRequestStatus::PENDING) {
            return Error::response('Only pending leave requests can be edited', 422);
        }

        $validator = Validator::make($request->all(), [
            'leaveTypeId' => 'sometimes|required|exists:leave_types,id',
            'fromDate' => 'sometimes|required|date|after_or_equal:today',
            'toDate' => 'sometimes|required|date|after_or_equal:fromDate',
            'userNotes' => 'sometimes|required|string|max:500',
            'isHalfDay' => 'nullable|boolean',
            'halfDayType' => 'required_if:isHalfDay,true|in:first_half,second_half',
            'emergencyContact' => 'nullable|string|max:100',
            'emergencyPhone' => 'nullable|string|max:50',
            'isAbroad' => 'nullable|boolean',
            'abroadLocation' => 'nullable|required_if:isAbroad,true|string|max:200',
            'useCompOff' => 'nullable|boolean',
            'compOffIds' => 'nullable|array',
            'compOffIds.*' => 'nullable|exists:compensatory_offs,id',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'document.mimes' => 'Only PDF, JPG, and PNG files are allowed',
            'document.max' => 'File size must not exceed 5MB',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            // Debug: Check what Laravel actually received
            Log::info('Raw Request Data', [
                'all' => $request->all(),
                'input' => $request->input(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
            ]);

            $fromDate = $request->has('fromDate') && $request->fromDate ? Carbon::parse($request->fromDate) : $leaveRequest->from_date;
            $toDate = $request->has('toDate') && $request->toDate ? Carbon::parse($request->toDate) : $leaveRequest->to_date;
            $isHalfDay = $request->has('isHalfDay') ? $request->boolean('isHalfDay') : $leaveRequest->is_half_day;

            // Debug logging
            Log::info('Update Leave Request Debug', [
                'request_fromDate' => $request->fromDate,
                'request_toDate' => $request->toDate,
                'request_userNotes' => $request->userNotes,
                'request_halfDayType' => $request->halfDayType,
                'parsed_fromDate' => $fromDate->toDateString(),
                'parsed_toDate' => $toDate->toDateString(),
                'old_fromDate' => $leaveRequest->from_date->toDateString(),
                'old_toDate' => $leaveRequest->to_date->toDateString(),
            ]);

            // Validate half-day constraint
            if ($isHalfDay && ! $fromDate->isSameDay($toDate)) {
                return Error::response('Half-day leave must be for a single date', 422);
            }

            // Calculate total working days for the leave period
            $totalDays = $this->calculateLeaveDays($fromDate, $toDate, $isHalfDay);

            // Handle compensatory off usage changes first
            $userId = auth()->id();

            // Check if comp off data is being updated (only update if explicitly provided)
            $isCompOffBeingUpdated = $request->has('useCompOff') || $request->has('compOffIds');

            if ($isCompOffBeingUpdated) {
                $compOffDaysUsed = 0;
                $useCompOff = $request->boolean('useCompOff');

                // Get comp off IDs - handle both array and individual keys (compOffIds[0], compOffIds[1], etc.)
                $compOffIds = $request->input('compOffIds', []);
                if (empty($compOffIds) && $useCompOff) {
                    // Try to get individual array elements sent as separate form fields
                    $compOffIds = [];
                    $index = 0;
                    while ($request->has("compOffIds.{$index}")) {
                        $compOffIds[] = $request->input("compOffIds.{$index}");
                        $index++;
                    }
                }

                // Get old comp off IDs
                $oldCompOffIds = $leaveRequest->comp_off_ids ? json_decode($leaveRequest->comp_off_ids, true) : [];

                // Release previously used comp offs
                if (! empty($oldCompOffIds)) {
                    CompensatoryOff::whereIn('id', $oldCompOffIds)
                        ->where('leave_request_id', $leaveRequest->id)
                        ->update([
                            'is_used' => false,
                            'used_date' => null,
                            'leave_request_id' => null,
                            'updated_by_id' => $userId,
                        ]);
                }

                // Apply new comp offs if requested
                if ($useCompOff && ! empty($compOffIds)) {
                    // Verify comp offs belong to user and are available
                    $compOffs = CompensatoryOff::whereIn('id', $compOffIds)
                        ->where('user_id', $userId)
                        ->where('status', 'approved')
                        ->where('is_used', false)
                        ->where('expiry_date', '>=', now()->toDateString())
                        ->get();

                    if ($compOffs->count() !== count($compOffIds)) {
                        return Error::response('One or more compensatory offs are invalid or unavailable', 422);
                    }

                    $compOffDaysUsed = $compOffs->sum('comp_off_days');

                    // Validate comp off days don't exceed the total working days
                    if ($compOffDaysUsed > $totalDays) {
                        return Error::response('Compensatory off days ('.$compOffDaysUsed.') cannot exceed the total leave days requested ('.$totalDays.')', 422);
                    }
                }
            } else {
                // Preserve existing comp off data if not being updated
                $useCompOff = $leaveRequest->use_comp_off;
                $compOffDaysUsed = $leaveRequest->comp_off_days_used;
                $compOffIds = $leaveRequest->comp_off_ids ? json_decode($leaveRequest->comp_off_ids, true) : [];
            }

            // Calculate days needed from leave balance after comp off
            $daysNeededFromLeaveBalance = max(0, $totalDays - $compOffDaysUsed);

            // Check leave balance if leave type changed or dates changed
            if ($request->has('leaveTypeId') || $request->has('fromDate') || $request->has('toDate') || $request->has('isHalfDay')) {
                // Check leave balance only if days are needed from leave balance
                if ($daysNeededFromLeaveBalance > 0) {
                    $leaveTypeId = $request->input('leaveTypeId', $leaveRequest->leave_type_id);
                    $currentYear = Carbon::now()->year;

                    $balance = UserAvailableLeave::where('user_id', auth()->id())
                        ->where('leave_type_id', $leaveTypeId)
                        ->where('year', $currentYear)
                        ->first();

                    if ($balance && $balance->available_leaves < $daysNeededFromLeaveBalance) {
                        return Error::response(
                            'Insufficient leave balance. You need '.$daysNeededFromLeaveBalance.' days but have '.$balance->available_leaves.' days available.',
                            422
                        );
                    }
                }

                // Check for overlapping leaves (excluding current request)
                $hasOverlap = LeaveRequest::where('user_id', auth()->id())
                    ->where('id', '!=', $id)
                    ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
                    ->where(function ($query) use ($fromDate, $toDate) {
                        $query->whereBetween('from_date', [$fromDate, $toDate])
                            ->orWhereBetween('to_date', [$fromDate, $toDate])
                            ->orWhere(function ($q) use ($fromDate, $toDate) {
                                $q->where('from_date', '<=', $fromDate)
                                    ->where('to_date', '>=', $toDate);
                            });
                    })
                    ->exists();

                if ($hasOverlap) {
                    return Error::response('You already have a leave request for the selected dates', 422);
                }
            }

            // Handle document upload if provided
            $updateData = [
                'leave_type_id' => $request->input('leaveTypeId', $leaveRequest->leave_type_id),
                'from_date' => $fromDate->format('Y-m-d'),
                'to_date' => $toDate->format('Y-m-d'),
                'is_half_day' => $isHalfDay,
                'half_day_type' => $isHalfDay ? ($request->has('halfDayType') && $request->halfDayType ? $request->halfDayType : $leaveRequest->half_day_type) : null,
                'total_days' => $totalDays,
                'user_notes' => $request->has('userNotes') && $request->userNotes ? $request->userNotes : $leaveRequest->user_notes,
                'emergency_contact' => $request->has('emergencyContact') && $request->emergencyContact ? $request->emergencyContact : $leaveRequest->emergency_contact,
                'emergency_phone' => $request->has('emergencyPhone') && $request->emergencyPhone ? $request->emergencyPhone : $leaveRequest->emergency_phone,
                'is_abroad' => $request->has('isAbroad') ? $request->boolean('isAbroad') : $leaveRequest->is_abroad,
                'abroad_location' => ($request->has('isAbroad') && $request->boolean('isAbroad')) ? ($request->has('abroadLocation') && $request->abroadLocation ? $request->abroadLocation : $leaveRequest->abroad_location) : null,
                'use_comp_off' => $useCompOff && ! empty($compOffIds),
                'comp_off_days_used' => $compOffDaysUsed,
                'comp_off_ids' => ! empty($compOffIds) ? json_encode($compOffIds) : null,
                'updated_by_id' => auth()->id(),
            ];

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
                Storage::disk('public')->putFileAs(Constants::BaseFolderLeaveRequestDocument, $file, $fileName);

                // Delete old document if exists
                if ($leaveRequest->document) {
                    Storage::disk('public')->delete($leaveRequest->document);
                }

                $updateData['document'] = Constants::BaseFolderLeaveRequestDocument.$fileName;
            }

            // Debug: Log what we're about to save
            Log::info('Update Data to be saved', $updateData);

            // Update leave request
            $leaveRequest->update($updateData);

            // Debug: Log what was actually saved
            Log::info('After Update', [
                'from_date' => $leaveRequest->from_date->toDateString(),
                'to_date' => $leaveRequest->to_date->toDateString(),
                'user_notes' => $leaveRequest->user_notes,
                'half_day_type' => $leaveRequest->half_day_type,
            ]);

            // Mark new compensatory offs as used (only if comp off was updated)
            if ($isCompOffBeingUpdated && $useCompOff && ! empty($compOffIds)) {
                CompensatoryOff::whereIn('id', $compOffIds)
                    ->update([
                        'is_used' => true,
                        'used_date' => now(),
                        'leave_request_id' => $leaveRequest->id,
                        'updated_by_id' => $userId,
                    ]);
            }

            DB::commit();

            return Success::response([
                'message' => 'Leave request updated successfully',
                'leaveRequest' => $this->formatLeaveRequest($leaveRequest->fresh(['leaveType']), true),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete newly uploaded file if exists
            if (isset($updateData['document']) && $updateData['document']) {
                Storage::disk('public')->delete($updateData['document']);
            }

            Log::error('Failed to update leave request: '.$e->getMessage());

            return Error::response('Failed to update leave request. Please try again.', 500);
        }
    }

    /**
     * Cancel a leave request
     *
     * DELETE /api/V1/leave/requests/{id}
     */
    public function cancelLeaveRequest(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        $leaveRequest = LeaveRequest::where('user_id', auth()->id())->find($id);

        if (! $leaveRequest) {
            return Error::response('Leave request not found', 404);
        }

        // Check if leave can be cancelled
        if ($leaveRequest->status === LeaveRequestStatus::CANCELLED || $leaveRequest->status === LeaveRequestStatus::CANCELLED_BY_ADMIN) {
            return Error::response('Leave request is already cancelled', 422);
        }

        if ($leaveRequest->status === LeaveRequestStatus::APPROVED && $leaveRequest->from_date->isPast()) {
            return Error::response('Cannot cancel approved leave that has already started or passed', 422);
        }

        if (! in_array($leaveRequest->status, [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])) {
            return Error::response('Leave request cannot be cancelled', 422);
        }

        try {
            DB::beginTransaction();

            // Use the model's cancel method to properly release Comp Offs
            $leaveRequest->cancel($request->input('reason', 'Cancelled by employee'), false);

            DB::commit();

            // Send notification
            NotificationHelper::notifyAdminHR(new CancelLeaveRequest($leaveRequest));

            return Success::response([
                'message' => 'Leave request cancelled successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel leave request: '.$e->getMessage());

            return Error::response('Failed to cancel leave request. Please try again.', 500);
        }
    }

    /**
     * Upload document for a leave request
     *
     * POST /api/V1/leave/requests/{id}/upload
     */
    public function uploadLeaveDocument(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max for mobile
        ], [
            'file.required' => 'Document file is required',
            'file.mimes' => 'Only PDF, JPG, and PNG files are allowed',
            'file.max' => 'File size must not exceed 5MB',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        $leaveRequest = LeaveRequest::where('user_id', auth()->id())->find($id);

        if (! $leaveRequest) {
            return Error::response('Leave request not found', 404);
        }

        try {
            $file = $request->file('file');
            $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
            Storage::disk('public')->putFileAs(Constants::BaseFolderLeaveRequestDocument, $file, $fileName);

            // Delete old document if exists
            if ($leaveRequest->document) {
                Storage::disk('public')->delete($leaveRequest->document);
            }

            $fullPath = Constants::BaseFolderLeaveRequestDocument.$fileName;

            $leaveRequest->update([
                'document' => $fullPath,
                'updated_by_id' => auth()->id(),
            ]);

            return Success::response([
                'message' => 'Document uploaded successfully',
                'documentUrl' => Storage::disk('public')->url($fullPath),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload leave document: '.$e->getMessage());

            return Error::response('Failed to upload document. Please try again.', 500);
        }
    }

    /**
     * Get leave statistics for current user
     *
     * GET /api/V1/leave/statistics
     */
    public function getLeaveStatistics(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = auth()->id();

        // Total leaves by status
        $totalPending = LeaveRequest::where('user_id', $userId)
            ->whereYear('from_date', $year)
            ->where('status', LeaveRequestStatus::PENDING)
            ->sum('total_days');

        $totalApproved = LeaveRequest::where('user_id', $userId)
            ->whereYear('from_date', $year)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->sum('total_days');

        $totalRejected = LeaveRequest::where('user_id', $userId)
            ->whereYear('from_date', $year)
            ->where('status', LeaveRequestStatus::REJECTED)
            ->sum('total_days');

        // Upcoming leaves (approved and future)
        $upcomingLeaves = LeaveRequest::where('user_id', $userId)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->where('from_date', '>=', Carbon::now())
            ->with('leaveType')
            ->orderBy('from_date')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                return $this->formatLeaveRequest($leave, false);
            });

        // Leaves by type
        $leavesByType = LeaveRequest::where('user_id', $userId)
            ->whereYear('from_date', $year)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->with('leaveType')
            ->get()
            ->groupBy('leave_type_id')
            ->map(function ($leaves, $leaveTypeId) {
                $leaveType = $leaves->first()->leaveType;

                return [
                    'leaveType' => [
                        'id' => $leaveType->id,
                        'name' => $leaveType->name,
                        'code' => $leaveType->code,
                    ],
                    'totalDays' => $leaves->sum('total_days'),
                    'count' => $leaves->count(),
                ];
            })
            ->values();

        return Success::response([
            'year' => $year,
            'totalPending' => $totalPending,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'upcomingLeaves' => $upcomingLeaves,
            'leavesByType' => $leavesByType,
        ]);
    }

    /**
     * Get team calendar (approved leaves of team members)
     *
     * GET /api/V1/leave/team-calendar
     */
    public function getTeamCalendar(Request $request)
    {
        $fromDate = $request->input('fromDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('toDate', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $user = User::find(auth()->id());

        // Get team members based on reporting structure
        $teamMemberIds = User::where('reporting_to_id', $user->reporting_to_id)
            ->orWhere('reporting_to_id', auth()->id())
            ->pluck('id');

        $leaves = LeaveRequest::whereIn('user_id', $teamMemberIds)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->whereBetween('from_date', [$fromDate, $toDate])
            ->with(['user', 'leaveType'])
            ->orderBy('from_date')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user' => [
                        'id' => $leave->user->id,
                        'name' => $leave->user->name,
                        'firstName' => $leave->user->first_name,
                        'lastName' => $leave->user->last_name,
                    ],
                    'leaveType' => [
                        'id' => $leave->leaveType->id,
                        'name' => $leave->leaveType->name,
                    ],
                    'fromDate' => $leave->from_date->format(Constants::DateFormat),
                    'toDate' => $leave->to_date->format(Constants::DateFormat),
                    'isHalfDay' => $leave->is_half_day,
                    'halfDayType' => $leave->half_day_type,
                    'totalDays' => $leave->total_days,
                ];
            });

        return Success::response([
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'leaves' => $leaves,
        ]);
    }

    /**
     * Calculate leave days excluding weekends and holidays based on settings
     */
    private function calculateLeaveDays(Carbon $fromDate, Carbon $toDate, bool $isHalfDay = false): float
    {
        if ($isHalfDay) {
            return 0.5;
        }

        $days = 0;
        $currentDate = $fromDate->copy();

        // Get settings
        $includeWeekends = config('settings.HRCore.weekend_included_in_leave', false);
        $includeHolidays = config('settings.HRCore.holidays_included_in_leave', false);

        while ($currentDate <= $toDate) {
            $isWeekend = $currentDate->isWeekend();
            $isHoliday = Holiday::whereDate('date', $currentDate)->exists();

            // Skip weekends if not included
            if (! $includeWeekends && $isWeekend) {
                $currentDate->addDay();

                continue;
            }

            // Skip holidays if not included
            if (! $includeHolidays && $isHoliday) {
                $currentDate->addDay();

                continue;
            }

            $days++;
            $currentDate->addDay();
        }

        return $days;
    }

    /**
     * Format leave request for API response
     */
    private function formatLeaveRequest(LeaveRequest $leave, bool $detailed = false): array
    {
        $data = [
            'id' => $leave->id,
            'fromDate' => $leave->from_date->format(Constants::DateFormat),
            'toDate' => $leave->to_date->format(Constants::DateFormat),
            'leaveType' => [
                'id' => $leave->leaveType->id,
                'name' => $leave->leaveType->name,
                'code' => $leave->leaveType->code,
            ],
            'isHalfDay' => $leave->is_half_day,
            'halfDayType' => $leave->half_day_type,
            'totalDays' => $leave->total_days,
            'status' => $leave->status->value,
            'createdAt' => $leave->created_at->format(Constants::DateTimeFormat),
        ];

        if ($detailed) {
            // Get comp off details if used
            $compOffDetails = [];
            if ($leave->use_comp_off && $leave->comp_off_ids) {
                $compOffIds = json_decode($leave->comp_off_ids, true);
                if (! empty($compOffIds)) {
                    $compOffs = CompensatoryOff::whereIn('id', $compOffIds)->get();
                    $compOffDetails = $compOffs->map(function ($compOff) {
                        return [
                            'id' => $compOff->id,
                            'compOffDays' => $compOff->comp_off_days,
                            'reason' => $compOff->reason,
                            'requestedDate' => $compOff->requested_date?->format(Constants::DateFormat),
                            'expiryDate' => $compOff->expiry_date?->format(Constants::DateFormat),
                            'status' => $compOff->status,
                        ];
                    })->toArray();
                }
            }

            $data = array_merge($data, [
                'userNotes' => $leave->user_notes,
                'emergencyContact' => $leave->emergency_contact,
                'emergencyPhone' => $leave->emergency_phone,
                'isAbroad' => $leave->is_abroad,
                'abroadLocation' => $leave->abroad_location,
                'document' => $leave->document,
                'documentUrl' => $leave->document ? Storage::disk('public')->url($leave->document) : null,
                'usesCompOff' => $leave->use_comp_off ?? false,
                'compOffDaysUsed' => $leave->comp_off_days_used ?? 0,
                'compOffIds' => $leave->comp_off_ids ? json_decode($leave->comp_off_ids, true) : [],
                'compOffDetails' => $compOffDetails,
                'approvalNotes' => $leave->approval_notes,
                'approvedBy' => $leave->approvedBy ? [
                    'id' => $leave->approvedBy->id,
                    'name' => $leave->approvedBy->name,
                ] : null,
                'approvedAt' => $leave->approved_at?->format(Constants::DateTimeFormat),
                'rejectedBy' => $leave->rejectedBy ? [
                    'id' => $leave->rejectedBy->id,
                    'name' => $leave->rejectedBy->name,
                ] : null,
                'rejectedAt' => $leave->rejected_at?->format(Constants::DateTimeFormat),
                'cancelReason' => $leave->cancel_reason,
                'cancelledBy' => $leave->cancelledBy ? [
                    'id' => $leave->cancelledBy->id,
                    'name' => $leave->cancelledBy->name,
                ] : null,
                'cancelledAt' => $leave->cancelled_at?->format(Constants::DateTimeFormat),
                'updatedAt' => $leave->updated_at->format(Constants::DateTimeFormat),
                'canCancel' => $leave->canBeCancelled(),
            ]);
        }

        return $data;
    }
}
