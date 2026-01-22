<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Holiday\CreateHolidayRequest;
use App\Http\Requests\Api\Holiday\GetHolidayRequest;
use App\Http\Requests\Api\Holiday\UpdateHolidayRequest;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * Get all holidays with pagination and filters
     */
    public function getAll(GetHolidayRequest $request): JsonResponse
    {
        $skip = $request->skip ?? 0;
        $take = $request->take ?? 10;

        $query = Holiday::query()
            ->with(['approvedBy:id,name', 'createdBy:id,name'])
            ->orderBy('date', 'asc');

        // Filter by year
        if ($request->has('year')) {
            $query->forYear($request->year);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        } else {
            // By default, show only active holidays
            $query->active();
        }

        // Filter by upcoming holidays
        if ($request->has('upcoming') && filter_var($request->upcoming, FILTER_VALIDATE_BOOLEAN)) {
            $query->upcoming();
        }

        // Filter by visible to employees
        if ($request->has('visible_to_employees') && filter_var($request->visible_to_employees, FILTER_VALIDATE_BOOLEAN)) {
            $query->visibleToEmployees();
        }

        $totalCount = $query->count();
        $holidays = $query->skip($skip)->take($take)->get();

        $holidays = $holidays->map(fn ($holiday) => $this->formatHoliday($holiday));

        $response = [
            'totalCount' => $totalCount,
            'values' => $holidays,
        ];

        return Success::response($response);
    }

    /**
     * Get a single holiday by ID
     */
    public function getById(int $id): JsonResponse
    {
        $holiday = Holiday::with(['approvedBy:id,name', 'createdBy:id,name', 'updatedBy:id,name'])
            ->find($id);

        if (! $holiday) {
            return Error::response('Holiday not found', 404);
        }

        return Success::response($this->formatHoliday($holiday, true));
    }

    /**
     * Create a new holiday
     */
    public function create(CreateHolidayRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Set created_by_id
            $data['created_by_id'] = auth()->id();

            // Create holiday
            $holiday = Holiday::create($data);

            return Success::response([
                'message' => 'Holiday created successfully',
                'holiday' => $this->formatHoliday($holiday->fresh(['approvedBy', 'createdBy'])),
            ], 201);
        } catch (\Exception $e) {
            return Error::response('Failed to create holiday: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update an existing holiday
     */
    public function update(UpdateHolidayRequest $request, int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (! $holiday) {
            return Error::response('Holiday not found', 404);
        }

        try {
            $data = $request->validated();

            // Set updated_by_id
            $data['updated_by_id'] = auth()->id();

            // Update holiday
            $holiday->update($data);

            return Success::response([
                'message' => 'Holiday updated successfully',
                'holiday' => $this->formatHoliday($holiday->fresh(['approvedBy', 'createdBy', 'updatedBy'])),
            ]);
        } catch (\Exception $e) {
            return Error::response('Failed to update holiday: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete a holiday (soft delete)
     */
    public function delete(int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (! $holiday) {
            return Error::response('Holiday not found', 404);
        }

        try {
            $holiday->delete();

            return Success::response([
                'message' => 'Holiday deleted successfully',
            ]);
        } catch (\Exception $e) {
            return Error::response('Failed to delete holiday: '.$e->getMessage(), 500);
        }
    }

    /**
     * Toggle holiday active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $holiday = Holiday::find($id);

        if (! $holiday) {
            return Error::response('Holiday not found', 404);
        }

        try {
            $holiday->is_active = ! $holiday->is_active;
            $holiday->updated_by_id = auth()->id();
            $holiday->save();

            return Success::response([
                'message' => 'Holiday status updated successfully',
                'is_active' => $holiday->is_active,
            ]);
        } catch (\Exception $e) {
            return Error::response('Failed to update holiday status: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get holidays for current user based on applicability
     */
    public function getMyHolidays(Request $request): JsonResponse
    {
        $user = auth()->user();
        $year = $request->year ?? now()->year;

        $holidays = Holiday::query()
            ->active()
            ->visibleToEmployees()
            ->forYear($year)
            ->orderBy('date', 'asc')
            ->get();

        // Filter holidays applicable to the user
        $applicableHolidays = $holidays->filter(fn ($holiday) => $holiday->isApplicableFor($user));

        $response = $applicableHolidays->map(fn ($holiday) => $this->formatHoliday($holiday))->values();

        return Success::response([
            'year' => $year,
            'totalCount' => $response->count(),
            'holidays' => $response,
        ]);
    }

    /**
     * Get upcoming holidays
     */
    public function getUpcoming(Request $request): JsonResponse
    {
        $limit = $request->limit ?? 10;

        $holidays = Holiday::query()
            ->active()
            ->visibleToEmployees()
            ->upcoming()
            ->limit($limit)
            ->get();

        $response = $holidays->map(fn ($holiday) => $this->formatHoliday($holiday));

        return Success::response([
            'totalCount' => $response->count(),
            'holidays' => $response,
        ]);
    }

    /**
     * Get holidays by year grouped by month
     */
    public function getByYearGrouped(Request $request): JsonResponse
    {
        $year = $request->year ?? now()->year;

        $holidays = Holiday::query()
            ->active()
            ->visibleToEmployees()
            ->forYear($year)
            ->orderBy('date', 'asc')
            ->get();

        $grouped = $holidays->groupBy(fn ($holiday) => $holiday->date->format('F'))
            ->map(fn ($monthHolidays) => $monthHolidays->map(fn ($holiday) => $this->formatHoliday($holiday)));

        return Success::response([
            'year' => $year,
            'months' => $grouped,
        ]);
    }

    /**
     * Format holiday data for response
     */
    private function formatHoliday(Holiday $holiday, bool $detailed = false): array
    {
        $data = [
            'id' => $holiday->id,
            'name' => $holiday->name,
            'code' => $holiday->code,
            'date' => Carbon::parse($holiday->date)->format(Constants::DateFormat),
            'year' => $holiday->year,
            'day' => $holiday->day,
            'type' => $holiday->type,
            'category' => $holiday->category,
            'is_optional' => $holiday->is_optional,
            'is_restricted' => $holiday->is_restricted,
            'is_recurring' => $holiday->is_recurring,
            'is_half_day' => $holiday->is_half_day,
            'is_active' => $holiday->is_active,
            'is_visible_to_employees' => $holiday->is_visible_to_employees,
            'color' => $holiday->color,
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'applicable_for' => $holiday->applicable_for,
                'departments' => $holiday->departments,
                'locations' => $holiday->locations,
                'employee_types' => $holiday->employee_types,
                'branches' => $holiday->branches,
                'specific_employees' => $holiday->specific_employees,
                'description' => $holiday->description,
                'notes' => $holiday->notes,
                'image' => $holiday->image,
                'sort_order' => $holiday->sort_order,
                'is_compensatory' => $holiday->is_compensatory,
                'compensatory_date' => $holiday->compensatory_date ? Carbon::parse($holiday->compensatory_date)->format(Constants::DateFormat) : null,
                'half_day_type' => $holiday->half_day_type,
                'half_day_start_time' => $holiday->half_day_start_time,
                'half_day_end_time' => $holiday->half_day_end_time,
                'send_notification' => $holiday->send_notification,
                'notification_days_before' => $holiday->notification_days_before,
                'approved_by' => $holiday->approvedBy ? [
                    'id' => $holiday->approvedBy->id,
                    'name' => $holiday->approvedBy->name,
                ] : null,
                'approved_at' => $holiday->approved_at ? Carbon::parse($holiday->approved_at)->format(Constants::DateTimeFormat) : null,
                'created_by' => $holiday->createdBy ? [
                    'id' => $holiday->createdBy->id,
                    'name' => $holiday->createdBy->name,
                ] : null,
                'updated_by' => $holiday->updatedBy ? [
                    'id' => $holiday->updatedBy->id,
                    'name' => $holiday->updatedBy->name,
                ] : null,
                'created_at' => Carbon::parse($holiday->created_at)->format(Constants::DateTimeFormat),
                'updated_at' => Carbon::parse($holiday->updated_at)->format(Constants::DateTimeFormat),
            ]);
        }

        return $data;
    }
}
