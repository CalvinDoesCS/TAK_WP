<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // Add permission middleware if needed
        // $this->middleware('permission:view_shifts')->only(['index', 'indexAjax']);
        // $this->middleware('permission:create_shifts')->only(['store']);
        // $this->middleware('permission:edit_shifts')->only(['edit', 'update', 'toggleStatus']);
        // $this->middleware('permission:delete_shifts')->only(['destroy']);
    }

    /**
     * Display shifts management view.
     */
    public function index()
    {
        return view('shift.index');
    }

    /**
     * Get DataTable data via AJAX using Yajra DataTables.
     */
    public function indexAjax(Request $request)
    {
        $query = Shift::query();

        return DataTables::of($query)
            ->editColumn('name', function ($shift) {
                return '<div class="d-flex flex-column">
                    <span class="fw-semibold">'.e($shift->name).'</span>
                </div>';
            })
            ->addColumn('timing', function ($shift) {
                if (! $shift->start_time || ! $shift->end_time) {
                    return '<span class="text-muted">N/A</span>';
                }

                return '<div class="d-flex flex-column">
                    <span><i class="bx bx-time-five"></i> '.Carbon::parse($shift->start_time)->format('h:i A').'</span>
                    <span><i class="bx bx-time"></i> '.Carbon::parse($shift->end_time)->format('h:i A').'</span>
                </div>';
            })
            ->addColumn('working_days', function ($shift) {
                $days = '';
                $dayMap = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];

                foreach ($dayMap as $key => $label) {
                    if ($shift->{$key}) {
                        $days .= '<span class="badge badge-sm bg-label-success me-1">'.$label.'</span>';
                    }
                }

                return $days ?: '<span class="text-muted">N/A</span>';
            })
            ->editColumn('status', function ($shift) {
                if (! $shift->status) {
                    return '<span class="badge bg-label-secondary">N/A</span>';
                }
                $statusValue = is_object($shift->status) ? $shift->status->value : $shift->status;
                $badge = $statusValue === 'active' ? 'bg-label-success' : 'bg-label-secondary';

                return '<span class="badge '.$badge.'">'.__(ucfirst($statusValue)).'</span>';
            })
            ->addColumn('actions', function ($shift) {
                // Check if shift is assigned to any user
                $isAssigned = User::where('shift_id', $shift->id)->exists();

                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editShift({$shift->id})",
                    ],
                    [
                        'label' => __('Toggle Status'),
                        'icon' => 'bx bx-refresh',
                        'onclick' => "toggleStatus({$shift->id})",
                    ],
                ];

                // Only show delete if not assigned
                if (! $isAssigned) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteShift({$shift->id})",
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $shift->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['name', 'timing', 'working_days', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Get active shifts for dropdown.
     */
    public function getActiveShiftsForDropdown(): JsonResponse
    {
        try {
            $shifts = Shift::where('status', Status::ACTIVE)
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $shifts,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching active shifts for dropdown: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load shifts.'),
            ], 500);
        }
    }

    /**
     * Store a newly created shift in storage.
     */
    public function store(StoreShiftRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Set defaults
            $data['status'] = Status::ACTIVE;
            $data['start_date'] = now()->format('Y-m-d');

            // Format times
            $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
            $data['end_time'] = Carbon::parse($data['end_time'])->format('H:i:s');

            $shift = Shift::create($data);

            Log::info("Shift created: ID {$shift->id}, Code {$shift->code} by User ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => __('Shift created successfully.'),
                'shift_id' => $shift->id,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating shift: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to create shift.'),
            ], 500);
        }
    }

    /**
     * Fetch data for editing the specified shift.
     */
    public function edit(Shift $shift): JsonResponse
    {
        try {
            // Prepare shift data for editing
            $shiftData = [
                'id' => $shift->id,
                'name' => $shift->name,
                'code' => $shift->code,
                'shift_type' => is_object($shift->shift_type) ? $shift->shift_type->value : $shift->shift_type,
                'notes' => $shift->notes,
                'start_time' => $shift->start_time ? Carbon::parse($shift->start_time)->format('H:i') : null,
                'end_time' => $shift->end_time ? Carbon::parse($shift->end_time)->format('H:i') : null,
                'monday' => $shift->monday ?? 0,
                'tuesday' => $shift->tuesday ?? 0,
                'wednesday' => $shift->wednesday ?? 0,
                'thursday' => $shift->thursday ?? 0,
                'friday' => $shift->friday ?? 0,
                'saturday' => $shift->saturday ?? 0,
                'sunday' => $shift->sunday ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $shiftData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching shift for edit: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to load shift data.'),
            ], 500);
        }
    }

    /**
     * Update the specified shift in storage.
     */
    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        try {
            $data = $request->validated();

            // Format times
            $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
            $data['end_time'] = Carbon::parse($data['end_time'])->format('H:i:s');

            $shift->update($data);

            Log::info("Shift updated: ID {$shift->id}, Code {$shift->code} by User ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => __('Shift updated successfully.'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating shift {$shift->id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to update shift.'),
            ], 500);
        }
    }

    /**
     * Toggle the active status of the specified shift.
     */
    public function toggleStatus(Shift $shift): JsonResponse
    {
        try {
            $newStatus = ($shift->status == Status::ACTIVE) ? Status::INACTIVE : Status::ACTIVE;
            $shift->status = $newStatus;
            $shift->save();

            Log::info("Shift status toggled: ID {$shift->id} to {$newStatus->value} by User ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => __('Shift status updated successfully.'),
                'newStatus' => $newStatus->value,
            ]);
        } catch (\Exception $e) {
            Log::error("Error toggling status for shift {$shift->id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to update status.'),
            ], 500);
        }
    }

    /**
     * Remove the specified shift from storage (Soft Delete).
     */
    public function destroy(Shift $shift): JsonResponse
    {
        try {
            // Check if shift is assigned to any active users
            $isAssigned = User::where('shift_id', $shift->id)->exists();
            if ($isAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cannot delete shift: It is currently assigned to one or more users.'),
                ], 409); // Conflict
            }

            $shiftId = $shift->id;
            $shiftCode = $shift->code;
            $shift->delete(); // Soft delete

            Log::info("Shift soft deleted: ID {$shiftId}, Code {$shiftCode} by User ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => __('Shift deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting shift {$shift->id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to delete shift.'),
            ], 500);
        }
    }
}
