<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Models\CompensatoryOff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class CompensatoryOffController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // Permission checks temporarily disabled
        // $this->middleware('permission:hrcore.view-comp-offs|hrcore.view-own-comp-offs|hrcore.view-team-comp-offs')->only(['index', 'indexAjax']);
        // $this->middleware('permission:hrcore.view-comp-offs')->only(['show']);
        // $this->middleware('permission:hrcore.create-comp-off')->only(['create', 'store']);
        // $this->middleware('permission:hrcore.edit-comp-off')->only(['edit', 'update']);
        // $this->middleware('permission:hrcore.delete-comp-off')->only(['destroy']);
        // $this->middleware('permission:hrcore.approve-comp-off|hrcore.reject-comp-off')->only(['approve', 'reject']);
        // $this->middleware('permission:hrcore.view-comp-off-reports')->only(['statistics']);
    }

    /**
     * Display a listing of compensatory offs.
     */
    public function index()
    {
        $employees = User::where('status', 'active')->orderBy('first_name')->get();

        // Get system-wide statistics (all employees) for admin panel
        $statistics = $this->getCompOffStatistics(null);

        return view('compensatory-off.index', compact('employees', 'statistics'));
    }

    /**
     * Get DataTable data for compensatory offs
     */
    public function indexAjax(Request $request)
    {
        $query = CompensatoryOff::with(['user', 'approvedBy', 'leaveRequest']);

        // Permission-based filtering disabled - show all
        // if (auth()->user()->can('hrcore.view-own-comp-offs') && ! auth()->user()->can('hrcore.view-comp-offs')) {
        //     // User can only see their own comp offs
        //     $query->where('user_id', auth()->id());
        // } elseif (auth()->user()->can('hrcore.view-team-comp-offs') && ! auth()->user()->can('hrcore.view-comp-offs')) {
        //     // User can see their team's comp offs
        //     $query->where(function ($q) {
        //         $q->where('user_id', auth()->id())
        //             ->orWhereHas('user', function ($subQ) {
        //                 $subQ->where('reporting_to_id', auth()->id());
        //             });
        //     });
        // }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Usage status filter
        if ($request->filled('usage_status')) {
            $usageStatus = $request->usage_status;
            if ($usageStatus === 'used') {
                $query->where('is_used', true);
            } elseif ($usageStatus === 'expired') {
                $query->where('status', 'approved')
                    ->where('is_used', false)
                    ->where('expiry_date', '<', now()->toDateString());
            } elseif ($usageStatus === 'available') {
                $query->where('status', 'approved')
                    ->where('is_used', false)
                    ->where('expiry_date', '>=', now()->toDateString());
            }
        }

        // Date range filter - handle both single range and separate dates
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if (str_contains($dateRange, ' to ')) {
                // Flatpickr range format: "YYYY-MM-DD to YYYY-MM-DD"
                $dates = explode(' to ', $dateRange);
                if (count($dates) === 2) {
                    $query->whereBetween('worked_date', [$dates[0], $dates[1]]);
                }
            }
        } elseif ($request->filled('from_date') || $request->filled('to_date')) {
            // Fallback to separate date filters
            if ($request->filled('from_date')) {
                $query->where('worked_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->where('worked_date', '<=', $request->to_date);
            }
        }

        return DataTables::of($query)
            ->addColumn('employee', function ($row) {
                return view('components.datatable-user', ['user' => $row->user])->render();
            })
            ->addColumn('worked_date_display', function ($row) {
                return Carbon::parse($row->worked_date)->format('d M Y');
            })
            ->addColumn('hours_worked_display', function ($row) {
                return '<span class="badge bg-label-info">'.$row->hours_worked.' '.__('hours').'</span>';
            })
            ->addColumn('comp_off_days_display', function ($row) {
                return '<span class="badge bg-label-primary">'.$row->comp_off_days.' '.__('days').'</span>';
            })
            ->addColumn('expiry_date_display', function ($row) {
                $expiryDate = Carbon::parse($row->expiry_date);
                $isExpired = $expiryDate->isPast() && ! $row->is_used;
                $color = $isExpired ? 'danger' : ($expiryDate->diffInDays(now()) <= 7 ? 'warning' : 'secondary');

                return '<span class="badge bg-label-'.$color.'">'.$expiryDate->format('d M Y').'</span>';
            })
            ->addColumn('status_display', function ($row) {
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $statusColors[$row->status] ?? 'secondary';

                return '<span class="badge bg-label-'.$color.'">'.ucfirst($row->status).'</span>';
            })
            ->addColumn('usage_status', function ($row) {
                if ($row->is_used) {
                    return '<span class="badge bg-label-success">'.__('Used').'</span>';
                } elseif ($row->status === 'approved' && Carbon::parse($row->expiry_date)->isPast()) {
                    return '<span class="badge bg-label-danger">'.__('Expired').'</span>';
                } elseif ($row->status === 'approved') {
                    return '<span class="badge bg-label-primary">'.__('Available').'</span>';
                } else {
                    return '<span class="badge bg-label-secondary">-</span>';
                }
            })
            ->addColumn('actions', function ($row) {
                $actions = [];

                // View action - always available
                $actions[] = [
                    'label' => __('View'),
                    'icon' => 'bx bx-show',
                    'onclick' => "viewCompensatoryOff({$row->id})",
                ];

                // Edit action - only for pending requests (permission check removed)
                if ($row->status === 'pending') {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'url' => route('hrcore.compensatory-offs.edit', $row->id),
                    ];
                }

                // Approve/Reject actions - available for all pending requests (permission check removed)
                if ($row->status === 'pending') {
                    $actions[] = [
                        'label' => __('Approve'),
                        'icon' => 'bx bx-check',
                        'onclick' => "approveCompensatoryOff({$row->id})",
                        'class' => 'text-success',
                    ];
                    $actions[] = [
                        'label' => __('Reject'),
                        'icon' => 'bx bx-x',
                        'onclick' => "rejectCompensatoryOff({$row->id})",
                        'class' => 'text-danger',
                    ];
                }

                // Delete action - available for all (permission check removed)
                $actions[] = [
                    'label' => __('Delete'),
                    'icon' => 'bx bx-trash',
                    'onclick' => "deleteCompensatoryOff({$row->id})",
                    'class' => 'text-danger',
                ];

                return view('components.datatable-actions', [
                    'id' => $row->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['employee', 'hours_worked_display', 'comp_off_days_display', 'expiry_date_display', 'status_display', 'usage_status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new compensatory off request.
     */
    public function create()
    {
        return view('compensatory-off.create');
    }

    /**
     * Store a newly created compensatory off request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'worked_date' => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:1000',
            'comp_off_days' => 'required|numeric|min:0.5|max:5',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Create compensatory off request
            $compOff = CompensatoryOff::create([
                'user_id' => auth()->id(),
                'worked_date' => $request->worked_date,
                'hours_worked' => $request->hours_worked,
                'comp_off_days' => $request->comp_off_days,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_by_id' => auth()->id(),
            ]);

            // Send notification to manager
            $this->sendCompOffNotification($compOff, 'created');

            DB::commit();

            return redirect()->route('hrcore.compensatory-offs.index')
                ->with('success', __('Compensatory off request submitted successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create compensatory off request: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to submit compensatory off request'))
                ->withInput();
        }
    }

    /**
     * Display the specified compensatory off.
     */
    public function show(Request $request, $id)
    {
        $compOff = CompensatoryOff::with([
            'user',
            'approvedBy',
            'rejectedBy',
            'leaveRequest',
        ])->findOrFail($id);

        // Permission check disabled
        // if (! $this->canViewCompOff($compOff)) {
        //     abort(403, 'Unauthorized');
        // }

        // Return partial view for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return view('compensatory-off.partials.details', compact('compOff'))->render();
        }

        return view('compensatory-off.show', compact('compOff'));
    }

    /**
     * Show the form for editing the specified compensatory off.
     */
    public function edit($id)
    {
        $compOff = CompensatoryOff::findOrFail($id);

        // Permission check disabled - only check status
        if ($compOff->status !== 'pending') {
            return redirect()->route('hrcore.compensatory-offs.index')
                ->with('error', __('Cannot edit this compensatory off request'));
        }

        return view('compensatory-off.edit', compact('compOff'));
    }

    /**
     * Update the specified compensatory off.
     */
    public function update(Request $request, $id)
    {
        $compOff = CompensatoryOff::findOrFail($id);

        // Check if can edit
        if ($compOff->status !== 'pending') {
            return redirect()->back()
                ->with('error', __('Cannot update '.$compOff->status.' compensatory off request'));
        }

        $validator = Validator::make($request->all(), [
            'worked_date' => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:1000',
            'comp_off_days' => 'required|numeric|min:0.5|max:5',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Update compensatory off request
            $compOff->update([
                'worked_date' => $request->worked_date,
                'hours_worked' => $request->hours_worked,
                'comp_off_days' => $request->comp_off_days,
                'reason' => $request->reason,
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('hrcore.compensatory-offs.show', $id)
                ->with('success', __('Compensatory off request updated successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update compensatory off request: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to update compensatory off request'))
                ->withInput();
        }
    }

    /**
     * Remove the specified compensatory off.
     */
    public function destroy($id)
    {
        $compOff = CompensatoryOff::findOrFail($id);

        // Permission check disabled
        // if (! auth()->user()->can('hrcore.delete-comp-off')) {
        //     return Error::response(__('Unauthorized'));
        // }

        try {
            $compOff->delete();

            return Success::response(['message' => __('Compensatory off request deleted successfully')]);
        } catch (\Exception $e) {
            Log::error('Failed to delete compensatory off request: '.$e->getMessage());

            return Error::response(__('Failed to delete compensatory off request'));
        }
    }

    /**
     * Approve compensatory off request
     */
    public function approve(Request $request, $id)
    {
        $compOff = CompensatoryOff::findOrFail($id);

        // Permission check disabled
        // if (! $this->canApproveCompOff($compOff)) {
        //     return Error::response(__('You are not authorized to approve this compensatory off'));
        // }

        if ($compOff->status !== 'pending') {
            return Error::response(__('Only pending compensatory off requests can be approved'));
        }

        DB::beginTransaction();
        try {
            // Update compensatory off request
            $compOff->update([
                'status' => 'approved',
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->notes,
            ]);

            // Send notification
            $this->sendCompOffNotification($compOff, 'approved');

            DB::commit();

            return Success::response(['message' => __('Compensatory off request approved successfully')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve compensatory off: '.$e->getMessage());

            return Error::response(__('Failed to approve compensatory off request'));
        }
    }

    /**
     * Reject compensatory off request
     */
    public function reject(Request $request, $id)
    {
        $compOff = CompensatoryOff::findOrFail($id);

        // Permission check disabled
        // if (! $this->canApproveCompOff($compOff)) {
        //     return Error::response(__('You are not authorized to reject this compensatory off'));
        // }

        if ($compOff->status !== 'pending') {
            return Error::response(__('Only pending compensatory off requests can be rejected'));
        }

        DB::beginTransaction();
        try {
            // Update compensatory off request
            $compOff->update([
                'status' => 'rejected',
                'rejected_by_id' => auth()->id(),
                'rejected_at' => now(),
                'approval_notes' => $request->reason,
            ]);

            // Send notification
            $this->sendCompOffNotification($compOff, 'rejected');

            DB::commit();

            return Success::response(['message' => __('Compensatory off request rejected')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject compensatory off: '.$e->getMessage());

            return Error::response(__('Failed to reject compensatory off request'));
        }
    }

    /**
     * Get compensatory off statistics
     * If user_id is provided, get stats for that user
     * If user_id is empty/null, get system-wide stats (all employees)
     */
    public function statistics(Request $request)
    {
        // If user_id is explicitly provided (even if empty string), use null for system-wide
        // Otherwise use auth user id
        $userId = $request->has('user_id') && empty($request->user_id) ? null : ($request->user_id ?? auth()->id());
        $statistics = $this->getCompOffStatistics($userId);

        return Success::response(['statistics' => $statistics]);
    }

    /**
     * Self-Service Methods
     */

    /**
     * Display current user's compensatory offs
     */
    public function myCompOffs()
    {
        // Get statistics for current user
        $statistics = $this->getCompOffStatistics(auth()->id());

        return view('compensatory-off.my-comp-offs', compact('statistics'));
    }

    /**
     * Get DataTable data for current user's compensatory offs
     */
    public function myCompOffsAjax(Request $request)
    {
        $query = CompensatoryOff::with(['approvedBy', 'leaveRequest'])
            ->where('user_id', auth()->id());

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('worked_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('worked_date', '<=', $request->to_date);
        }

        // Handle search
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('worked_date', 'like', "%{$searchValue}%")
                    ->orWhere('hours_worked', 'like', "%{$searchValue}%")
                    ->orWhere('comp_off_days', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhere('reason', 'like', "%{$searchValue}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('worked_date_display', function ($row) {
                return Carbon::parse($row->worked_date)->format('d M Y');
            })
            ->addColumn('hours_worked_display', function ($row) {
                return '<span class="badge bg-label-info">'.$row->hours_worked.' '.__('hours').'</span>';
            })
            ->addColumn('comp_off_days_display', function ($row) {
                return '<span class="badge bg-label-primary">'.$row->comp_off_days.' '.__('days').'</span>';
            })
            ->addColumn('expiry_date_display', function ($row) {
                $expiryDate = Carbon::parse($row->expiry_date);
                $isExpired = $expiryDate->isPast() && ! $row->is_used;
                $color = $isExpired ? 'danger' : ($expiryDate->diffInDays(now()) <= 7 ? 'warning' : 'secondary');

                return '<span class="badge bg-label-'.$color.'">'.$expiryDate->format('d M Y').'</span>';
            })
            ->addColumn('status_display', function ($row) {
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $statusColors[$row->status] ?? 'secondary';

                return '<span class="badge bg-label-'.$color.'">'.ucfirst($row->status).'</span>';
            })
            ->addColumn('usage_status', function ($row) {
                if ($row->is_used) {
                    return '<span class="badge bg-label-success">'.__('Used').'</span>';
                } elseif ($row->status === 'approved' && Carbon::parse($row->expiry_date)->isPast()) {
                    return '<span class="badge bg-label-danger">'.__('Expired').'</span>';
                } elseif ($row->status === 'approved') {
                    return '<span class="badge bg-label-primary">'.__('Available').'</span>';
                } else {
                    return '<span class="badge bg-label-secondary">-</span>';
                }
            })
            ->addColumn('actions', function ($row) {
                $actions = [];

                // View action
                $actions[] = [
                    'label' => __('View'),
                    'icon' => 'bx bx-show',
                    'onclick' => "viewCompensatoryOff({$row->id})",
                ];

                // Edit action - only for pending requests
                if ($row->status === 'pending') {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editCompensatoryOff({$row->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $row->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['hours_worked_display', 'comp_off_days_display', 'expiry_date_display', 'status_display', 'usage_status', 'actions'])
            ->make(true);
    }

    /**
     * Store a compensatory off request from self-service
     */
    public function requestCompOff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'worked_date' => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:1000',
            'comp_off_days' => 'required|numeric|min:0.5|max:5',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            // Create compensatory off request
            $compOff = CompensatoryOff::create([
                'user_id' => auth()->id(),
                'worked_date' => $request->worked_date,
                'hours_worked' => $request->hours_worked,
                'comp_off_days' => $request->comp_off_days,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_by_id' => auth()->id(),
            ]);

            // Send notification to manager
            $this->sendCompOffNotification($compOff, 'created');

            DB::commit();

            return Success::response(['message' => __('Compensatory off request submitted successfully')]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create compensatory off request: '.$e->getMessage());

            return Error::response(__('Failed to submit compensatory off request'));
        }
    }

    /**
     * Get compensatory off details for editing in self-service
     */
    public function getCompOffForEdit($id)
    {
        $compOff = CompensatoryOff::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        return Success::response(['compOff' => $compOff]);
    }

    /**
     * Update compensatory off request from self-service
     */
    public function updateCompOff(Request $request, $id)
    {
        $compOff = CompensatoryOff::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'worked_date' => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:1000',
            'comp_off_days' => 'required|numeric|min:0.5|max:5',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            // Update compensatory off request
            $compOff->update([
                'worked_date' => $request->worked_date,
                'hours_worked' => $request->hours_worked,
                'comp_off_days' => $request->comp_off_days,
                'reason' => $request->reason,
                'updated_by_id' => auth()->id(),
            ]);

            DB::commit();

            return Success::response(['message' => __('Compensatory off request updated successfully')]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update compensatory off request: '.$e->getMessage());

            return Error::response(__('Failed to update compensatory off request'));
        }
    }

    /**
     * Get available comp off balance for current user or specified user
     */
    public function getAvailableBalance(Request $request)
    {
        try {
            // Use the provided user_id if available, otherwise use current user
            $userId = $request->input('user_id', auth()->id());

            // Get available comp offs (approved, not used, not expired)
            $compOffs = CompensatoryOff::where('user_id', $userId)
                ->where('status', 'approved')
                ->where('is_used', false)
                ->where('expiry_date', '>=', now()->toDateString())
                ->orderBy('worked_date', 'asc') // FIFO - oldest first
                ->get();

            $balance = $compOffs->sum('comp_off_days');

            return Success::response([
                'balance' => $balance,
                'comp_offs' => $compOffs->map(function ($compOff) {
                    return [
                        'id' => $compOff->id,
                        'worked_date' => $compOff->worked_date->format('Y-m-d'),
                        'comp_off_days' => $compOff->comp_off_days,
                        'expiry_date' => $compOff->expiry_date->format('Y-m-d'),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch comp off balance: '.$e->getMessage());

            return Error::response(__('Failed to fetch compensatory off balance'));
        }
    }

    /**
     * Helper Methods
     */
    private function canViewCompOff($compOff)
    {
        return auth()->user()->can('hrcore.view-comp-offs') ||
          $compOff->user_id === auth()->id() ||
          $compOff->user->reporting_to_id === auth()->id();
    }

    private function canApproveCompOff($compOff)
    {
        return auth()->user()->can('hrcore.approve-comp-off') ||
          $compOff->user->reporting_to_id === auth()->id();
    }

    private function sendCompOffNotification($compOff, $action)
    {
        try {
            // Implementation for notifications
            // This would send email/in-app notifications based on action
        } catch (\Exception $e) {
            Log::error('Failed to send compensatory off notification: '.$e->getMessage());
        }
    }

    private function getCompOffStatistics($userId)
    {
        $statistics = [
            'total_earned' => 0,
            'available' => 0,
            'used' => 0,
            'expired' => 0,
            'pending' => 0,
            'by_month' => [],
        ];

        // Get compensatory offs - if userId is null, get all (system-wide), otherwise get for specific user
        $query = CompensatoryOff::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $compOffs = $query->get();

        $statistics['total_earned'] = $compOffs->where('status', 'approved')->sum('comp_off_days');
        $statistics['available'] = $compOffs->where('status', 'approved')
            ->filter(function ($compOff) {
                return ! $compOff->is_used && Carbon::parse($compOff->expiry_date)->isFuture();
            })->sum('comp_off_days');
        $statistics['used'] = $compOffs->filter(function ($compOff) {
            return $compOff->is_used == true;
        })->sum('comp_off_days');
        $statistics['expired'] = $compOffs->where('status', 'approved')
            ->filter(function ($compOff) {
                return ! $compOff->is_used && Carbon::parse($compOff->expiry_date)->isPast();
            })->sum('comp_off_days');
        $statistics['pending'] = $compOffs->where('status', 'pending')->sum('comp_off_days');

        // Monthly breakdown for current year
        $currentYear = Carbon::now()->year;
        for ($month = 1; $month <= 12; $month++) {
            $monthlyEarned = $compOffs->filter(function ($compOff) use ($currentYear, $month) {
                return Carbon::parse($compOff->worked_date)->year === $currentYear &&
                  Carbon::parse($compOff->worked_date)->month === $month &&
                  $compOff->status === 'approved';
            })->sum('comp_off_days');

            $statistics['by_month'][] = [
                'month' => Carbon::create()->month($month)->format('M'),
                'earned' => $monthlyEarned,
            ];
        }

        return $statistics;
    }
}
