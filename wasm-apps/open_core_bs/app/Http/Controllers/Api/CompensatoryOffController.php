<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Http\Controllers\Controller;
use App\Models\CompensatoryOff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompensatoryOffController extends Controller
{
    /**
     * Get paginated list of compensatory offs for current user
     *
     * GET /api/V1/comp-off/list
     * Query params: skip, take, status
     */
    public function getCompensatoryOffs(Request $request)
    {
        $skip = $request->input('skip', 0);
        $take = $request->input('take', 20);

        $query = CompensatoryOff::query()
            ->where('user_id', auth()->id())
            ->with(['approvedBy', 'leaveRequest'])
            ->orderBy('worked_date', 'desc');

        // Apply status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $totalCount = $query->count();
        $compOffs = $query->skip($skip)->take($take)->get();

        $values = $compOffs->map(function ($compOff) {
            return $this->formatCompensatoryOff($compOff, false);
        });

        return Success::response([
            'totalCount' => $totalCount,
            'values' => $values,
        ]);
    }

    /**
     * Get single compensatory off details
     *
     * GET /api/V1/comp-off/{id}
     */
    public function getCompensatoryOff(int $id)
    {
        $compOff = CompensatoryOff::with(['approvedBy', 'leaveRequest'])
            ->where('user_id', auth()->id())
            ->find($id);

        if (! $compOff) {
            return Error::response('Compensatory off not found', 404);
        }

        return Success::response($this->formatCompensatoryOff($compOff, true));
    }

    /**
     * Get available compensatory off balance
     *
     * GET /api/V1/comp-off/balance
     */
    public function getBalance()
    {
        $userId = auth()->id();

        // Get available balance
        $availableBalance = CompensatoryOff::getAvailableBalance($userId);

        // Get total approved comp offs
        $totalApproved = CompensatoryOff::where('user_id', $userId)
            ->where('status', 'approved')
            ->sum('comp_off_days');

        // Get used comp offs
        $totalUsed = CompensatoryOff::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('is_used', true)
            ->sum('comp_off_days');

        // Get expired comp offs
        $totalExpired = CompensatoryOff::where('user_id', $userId)
            ->expired()
            ->sum('comp_off_days');

        // Get pending comp offs
        $totalPending = CompensatoryOff::where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('comp_off_days');

        return Success::response([
            'available' => $availableBalance,
            'totalApproved' => $totalApproved,
            'totalUsed' => $totalUsed,
            'totalExpired' => $totalExpired,
            'totalPending' => $totalPending,
        ]);
    }

    /**
     * Create a new compensatory off request
     *
     * POST /api/V1/comp-off/request
     */
    public function createCompensatoryOff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workedDate' => 'required|date|before_or_equal:today',
            'hoursWorked' => 'required|numeric|min:1|max:24',
            'reason' => 'required|string|max:500',
        ], [
            'workedDate.required' => 'Worked date is required',
            'workedDate.before_or_equal' => 'Worked date cannot be in the future',
            'hoursWorked.required' => 'Hours worked is required',
            'hoursWorked.min' => 'Hours worked must be at least 1 hour',
            'hoursWorked.max' => 'Hours worked cannot exceed 24 hours',
            'reason.required' => 'Reason is required',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $workedDate = Carbon::parse($request->workedDate);
            $hoursWorked = $request->hoursWorked;

            // Calculate comp off days (1 day for 8+ hours, 0.5 for less)
            $compOffDays = $hoursWorked >= 8 ? 1 : 0.5;

            // Check if comp off already exists for this date
            $existing = CompensatoryOff::where('user_id', auth()->id())
                ->whereDate('worked_date', $workedDate)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if ($existing) {
                return Error::response('You already have a comp off request for this date', 422);
            }

            // Create compensatory off
            $compOff = CompensatoryOff::create([
                'user_id' => auth()->id(),
                'worked_date' => $workedDate->format('Y-m-d'),
                'hours_worked' => $hoursWorked,
                'comp_off_days' => $compOffDays,
                'reason' => $request->reason,
                'status' => 'pending',
                'is_used' => false,
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return Success::response([
                'message' => 'Compensatory off request submitted successfully',
                'compOffId' => $compOff->id,
                'compOff' => $this->formatCompensatoryOff($compOff->fresh(), true),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create compensatory off: '.$e->getMessage());

            return Error::response('Failed to create compensatory off. Please try again.', 500);
        }
    }

    /**
     * Update a pending compensatory off request
     *
     * PUT /api/V1/comp-off/{id}
     */
    public function updateCompensatoryOff(Request $request, int $id)
    {
        $compOff = CompensatoryOff::where('user_id', auth()->id())->find($id);

        if (! $compOff) {
            return Error::response('Compensatory off not found', 404);
        }

        if ($compOff->status !== 'pending') {
            return Error::response('Only pending compensatory offs can be edited', 422);
        }

        $validator = Validator::make($request->all(), [
            'workedDate' => 'sometimes|required|date|before_or_equal:today',
            'hoursWorked' => 'sometimes|required|numeric|min:1|max:24',
            'reason' => 'sometimes|required|string|max:500',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $workedDate = $request->has('workedDate') ? Carbon::parse($request->workedDate) : $compOff->worked_date;
            $hoursWorked = $request->input('hoursWorked', $compOff->hours_worked);

            // Calculate comp off days
            $compOffDays = $hoursWorked >= 8 ? 1 : 0.5;

            // Check for duplicates if date changed
            if ($request->has('workedDate') && ! $workedDate->isSameDay($compOff->worked_date)) {
                $existing = CompensatoryOff::where('user_id', auth()->id())
                    ->where('id', '!=', $id)
                    ->whereDate('worked_date', $workedDate)
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();

                if ($existing) {
                    return Error::response('You already have a comp off request for this date', 422);
                }
            }

            $compOff->update([
                'worked_date' => $workedDate->format('Y-m-d'),
                'hours_worked' => $hoursWorked,
                'comp_off_days' => $compOffDays,
                'reason' => $request->input('reason', $compOff->reason),
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return Success::response([
                'message' => 'Compensatory off updated successfully',
                'compOff' => $this->formatCompensatoryOff($compOff->fresh(), true),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update compensatory off: '.$e->getMessage());

            return Error::response('Failed to update compensatory off. Please try again.', 500);
        }
    }

    /**
     * Delete a pending compensatory off request
     *
     * DELETE /api/V1/comp-off/{id}
     */
    public function deleteCompensatoryOff(int $id)
    {
        $compOff = CompensatoryOff::where('user_id', auth()->id())->find($id);

        if (! $compOff) {
            return Error::response('Compensatory off not found', 404);
        }

        if ($compOff->status !== 'pending') {
            return Error::response('Only pending compensatory offs can be deleted', 422);
        }

        try {
            $compOff->delete();

            return Success::response([
                'message' => 'Compensatory off deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete compensatory off: '.$e->getMessage());

            return Error::response('Failed to delete compensatory off. Please try again.', 500);
        }
    }

    /**
     * Get compensatory off statistics
     *
     * GET /api/V1/comp-off/statistics
     */
    public function getStatistics(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = auth()->id();

        // Count by status
        $totalPending = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'pending')
            ->count();

        $totalApproved = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'approved')
            ->count();

        $totalRejected = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'rejected')
            ->count();

        // Days by status
        $daysPending = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'pending')
            ->sum('comp_off_days');

        $daysApproved = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'approved')
            ->sum('comp_off_days');

        $daysUsed = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->where('status', 'approved')
            ->where('is_used', true)
            ->sum('comp_off_days');

        $daysExpired = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->expired()
            ->sum('comp_off_days');

        // Recent comp offs
        $recentCompOffs = CompensatoryOff::where('user_id', $userId)
            ->whereYear('worked_date', $year)
            ->with('approvedBy')
            ->orderBy('worked_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($compOff) {
                return $this->formatCompensatoryOff($compOff, false);
            });

        return Success::response([
            'year' => $year,
            'counts' => [
                'pending' => $totalPending,
                'approved' => $totalApproved,
                'rejected' => $totalRejected,
            ],
            'days' => [
                'pending' => $daysPending,
                'approved' => $daysApproved,
                'used' => $daysUsed,
                'expired' => $daysExpired,
            ],
            'recentCompOffs' => $recentCompOffs,
        ]);
    }

    /**
     * Format compensatory off for API response
     */
    private function formatCompensatoryOff(CompensatoryOff $compOff, bool $detailed = false): array
    {
        $data = [
            'id' => $compOff->id,
            'workedDate' => $compOff->worked_date->format(Constants::DateFormat),
            'hoursWorked' => $compOff->hours_worked,
            'compOffDays' => $compOff->comp_off_days,
            'expiryDate' => $compOff->expiry_date->format(Constants::DateFormat),
            'status' => $compOff->status,
            'isUsed' => $compOff->is_used,
            'canBeUsed' => $compOff->canBeUsed(),
            'usedDate' => $compOff->used_date?->format(Constants::DateFormat),
            'createdAt' => $compOff->created_at->format(Constants::DateTimeFormat),
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'reason' => $compOff->reason,
                'usedDate' => $compOff->used_date?->format(Constants::DateFormat),
                'leaveRequest' => $compOff->leaveRequest ? [
                    'id' => $compOff->leaveRequest->id,
                    'fromDate' => $compOff->leaveRequest->from_date->format(Constants::DateFormat),
                    'toDate' => $compOff->leaveRequest->to_date->format(Constants::DateFormat),
                ] : null,
                'approvalNotes' => $compOff->approval_notes,
                'approvedBy' => $compOff->approvedBy ? [
                    'id' => $compOff->approvedBy->id,
                    'name' => $compOff->approvedBy->name,
                ] : null,
                'approvedAt' => $compOff->approved_at?->format(Constants::DateTimeFormat),
                'updatedAt' => $compOff->updated_at->format(Constants::DateTimeFormat),
            ]);
        }

        return $data;
    }
}
