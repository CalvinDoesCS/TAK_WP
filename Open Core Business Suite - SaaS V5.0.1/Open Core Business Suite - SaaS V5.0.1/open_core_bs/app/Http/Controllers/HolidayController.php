<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Helpers\FormattingHelper;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class HolidayController extends Controller
{
    public function __construct()
    {
        // PERMISSIONS TEMPORARILY DISABLED
        // $this->middleware('permission:hrcore.view-holidays', ['only' => ['index', 'datatable', 'show']]);
        // $this->middleware('permission:hrcore.create-holidays', ['only' => ['store']]);
        // $this->middleware('permission:hrcore.edit-holidays', ['only' => ['update', 'toggleStatus']]);
        // $this->middleware('permission:hrcore.delete-holidays', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $employees = User::select('id', 'first_name', 'last_name', 'email')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $holidayTypes = [
            'public' => __('Public Holiday'),
            'religious' => __('Religious Holiday'),
            'regional' => __('Regional Holiday'),
            'optional' => __('Optional Holiday'),
            'company' => __('Company Holiday'),
            'special' => __('Special Holiday'),
        ];

        $categories = [
            'national' => __('National'),
            'state' => __('State'),
            'cultural' => __('Cultural'),
            'festival' => __('Festival'),
            'company_event' => __('Company Event'),
            'other' => __('Other'),
        ];

        $applicableOptions = [
            'all' => __('All Employees'),
            'department' => __('Specific Departments'),
            'location' => __('Specific Locations'),
            'employee_type' => __('Specific Employee Types'),
            'branch' => __('Specific Branches'),
            'custom' => __('Specific Employees'),
        ];

        return view('holidays.index', compact(
            'departments',
            'employees',
            'holidayTypes',
            'categories',
            'applicableOptions'
        ));
    }

    /**
     * Get data for DataTable via AJAX
     */
    public function datatable(Request $request)
    {
        $holidays = Holiday::query()->with(['createdBy', 'updatedBy']);

        // Apply filters
        if ($request->filled('year')) {
            $holidays->where('year', $request->year);
        }

        if ($request->filled('type')) {
            $holidays->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $holidays->where('is_active', $isActive);
        }

        return DataTables::of($holidays)
            ->addColumn('date_formatted', function ($holiday) {
                return '<div class="text-nowrap">'.
                    '<div class="fw-medium">'.FormattingHelper::formatDate($holiday->date).'</div>'.
                    '<small class="text-muted">'.$holiday->day.'</small>'.
                    '</div>';
            })
            ->addColumn('type_badge', function ($holiday) {
                $colors = [
                    'public' => 'primary',
                    'religious' => 'info',
                    'regional' => 'warning',
                    'optional' => 'secondary',
                    'company' => 'success',
                    'special' => 'danger',
                ];
                $color = $colors[$holiday->type] ?? 'secondary';

                return '<span class="badge bg-label-'.$color.'">'.ucfirst($holiday->type).'</span>';
            })
            ->addColumn('applicability', function ($holiday) {
                if ($holiday->applicable_for === 'all') {
                    return '<span class="badge bg-success"><i class="bx bx-check-circle"></i> '.__('All Employees').'</span>';
                }

                $label = match ($holiday->applicable_for) {
                    'department' => __('Departments'),
                    'location' => __('Locations'),
                    'employee_type' => __('Employee Types'),
                    'branch' => __('Branches'),
                    'custom' => __('Custom'),
                    default => ucfirst(str_replace('_', ' ', $holiday->applicable_for)),
                };

                $count = 0;
                if ($holiday->applicable_for === 'department' && $holiday->departments) {
                    $count = count($holiday->departments);
                } elseif ($holiday->applicable_for === 'custom' && $holiday->specific_employees) {
                    $count = count($holiday->specific_employees);
                }

                $badge = '<span class="badge bg-info">'.$label.'</span>';
                if ($count > 0) {
                    $badge .= ' <small class="text-muted">('.$count.')</small>';
                }

                return $badge;
            })
            ->addColumn('properties', function ($holiday) {
                $badges = '';

                if ($holiday->is_optional) {
                    $badges .= '<span class="badge bg-label-secondary me-1" title="'.__('Optional').'"><i class="bx bx-info-circle"></i></span>';
                }
                if ($holiday->is_half_day) {
                    $badges .= '<span class="badge bg-label-info me-1" title="'.__('Half Day').'"><i class="bx bx-time"></i></span>';
                }
                if ($holiday->is_recurring) {
                    $badges .= '<span class="badge bg-label-success me-1" title="'.__('Recurring').'"><i class="bx bx-repeat"></i></span>';
                }
                if ($holiday->is_compensatory) {
                    $badges .= '<span class="badge bg-label-primary me-1" title="'.__('Compensatory').'"><i class="bx bx-calendar-edit"></i></span>';
                }
                if ($holiday->is_restricted) {
                    $badges .= '<span class="badge bg-label-warning me-1" title="'.__('Restricted').'"><i class="bx bx-lock"></i></span>';
                }

                return $badges ?: '<span class="text-muted">-</span>';
            })
            ->addColumn('status_badge', function ($holiday) {
                if ($holiday->is_active) {
                    return '<span class="badge bg-success">'.__('Active').'</span>';
                } else {
                    return '<span class="badge bg-secondary">'.__('Inactive').'</span>';
                }
            })
            ->addColumn('actions', function ($holiday) {
                $actions = [];

                // PERMISSIONS TEMPORARILY DISABLED
                // if (auth()->user()->can('hrcore.edit-holidays')) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editHoliday({$holiday->id})",
                    ];

                    $actions[] = [
                        'label' => $holiday->is_active ? __('Deactivate') : __('Activate'),
                        'icon' => $holiday->is_active ? 'bx bx-x-circle' : 'bx bx-check-circle',
                        'onclick' => "toggleStatus({$holiday->id})",
                    ];
                // }

                // PERMISSIONS TEMPORARILY DISABLED
                // if (auth()->user()->can('hrcore.delete-holidays')) {
                    if (! empty($actions)) {
                        $actions[] = ['divider' => true];
                    }
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteHoliday({$holiday->id})",
                        'class' => 'text-danger',
                    ];
                // }

                return view('components.datatable-actions', [
                    'id' => $holiday->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['date_formatted', 'type_badge', 'applicability', 'properties', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHolidayRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Set year and day from date
            $date = Carbon::parse($data['date']);
            $data['year'] = $date->year;
            $data['day'] = $date->format('l');
            $data['is_active'] = true;

            $holiday = Holiday::create($data);

            DB::commit();

            return Success::response([
                'message' => __('Holiday created successfully!'),
                'holiday' => $holiday,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create holiday: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return Error::response(__('Failed to create holiday. Please try again.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            return Success::response([
                'id' => $holiday->id,
                'name' => $holiday->name,
                'code' => $holiday->code,
                'date' => $holiday->date->format('Y-m-d'),
                'date_formatted' => FormattingHelper::formatDate($holiday->date),
                'type' => $holiday->type,
                'category' => $holiday->category,
                'description' => $holiday->description,
                'notes' => $holiday->notes,
                'color' => $holiday->color,
                'is_optional' => $holiday->is_optional,
                'is_restricted' => $holiday->is_restricted,
                'is_recurring' => $holiday->is_recurring,
                'is_half_day' => $holiday->is_half_day,
                'half_day_type' => $holiday->half_day_type,
                'half_day_start_time' => $holiday->half_day_start_time,
                'half_day_end_time' => $holiday->half_day_end_time,
                'is_compensatory' => $holiday->is_compensatory,
                'compensatory_date' => $holiday->compensatory_date?->format('Y-m-d'),
                'applicable_for' => $holiday->applicable_for,
                'departments' => $holiday->departments,
                'locations' => $holiday->locations,
                'employee_types' => $holiday->employee_types,
                'branches' => $holiday->branches,
                'specific_employees' => $holiday->specific_employees,
                'is_visible_to_employees' => $holiday->is_visible_to_employees,
                'send_notification' => $holiday->send_notification,
                'notification_days_before' => $holiday->notification_days_before,
                'is_active' => $holiday->is_active,
                'sort_order' => $holiday->sort_order,
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Holiday not found'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHolidayRequest $request, $id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            DB::beginTransaction();

            $data = $request->validated();

            // Set year and day from date
            $date = Carbon::parse($data['date']);
            $data['year'] = $date->year;
            $data['day'] = $date->format('l');

            $holiday->update($data);

            DB::commit();

            return Success::response([
                'message' => __('Holiday updated successfully!'),
                'holiday' => $holiday->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update holiday: '.$e->getMessage(), [
                'exception' => $e,
                'holiday_id' => $id,
                'request' => $request->all(),
            ]);

            return Error::response(__('Failed to update holiday. Please try again.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);

            DB::beginTransaction();
            $holiday->delete();
            DB::commit();

            return Success::response([
                'message' => __('Holiday deleted successfully!'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete holiday: '.$e->getMessage(), [
                'exception' => $e,
                'holiday_id' => $id,
            ]);

            return Error::response(__('Failed to delete holiday. Please try again.'));
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->is_active = ! $holiday->is_active;
            $holiday->save();

            $status = $holiday->is_active ? __('activated') : __('deactivated');

            return Success::response([
                'message' => __('Holiday :status successfully!', ['status' => $status]),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update holiday status: '.$e->getMessage(), [
                'exception' => $e,
                'holiday_id' => $id,
            ]);

            return Error::response(__('Failed to update holiday status. Please try again.'));
        }
    }

    /**
     * Display holidays for employees (My Holidays view)
     */
    public function myHolidays()
    {
        $currentYear = date('Y');
        $user = auth()->user();

        // Get holidays applicable to current user
        $holidays = Holiday::active()
            ->visibleToEmployees()
            ->whereYear('date', $currentYear)
            ->orderBy('date')
            ->get()
            ->filter(function ($holiday) use ($user) {
                return $holiday->isApplicableFor($user);
            });

        // Group holidays by month
        $holidaysByMonth = $holidays->groupBy(function ($holiday) {
            return $holiday->date->format('F');
        });

        // Get upcoming holidays
        $upcomingHolidays = Holiday::active()
            ->visibleToEmployees()
            ->upcoming()
            ->limit(5)
            ->get()
            ->filter(function ($holiday) use ($user) {
                return $holiday->isApplicableFor($user);
            });

        // Get holiday count by type
        $totalHolidays = $holidays->count();
        $pastHolidays = $holidays->where('date', '<', now())->count();
        $futureHolidays = $holidays->where('date', '>=', now())->count();

        return view('holidays.my-holidays', compact(
            'holidays',
            'holidaysByMonth',
            'upcomingHolidays',
            'totalHolidays',
            'pastHolidays',
            'futureHolidays',
            'currentYear'
        ));
    }
}
