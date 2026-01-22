<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Models\ExpenseRequest;
use App\Models\ExpenseType;
use App\Models\Settings;
use App\Models\User;
use App\Notifications\Expense\ExpenseRequestApproval;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenseTypes = ExpenseType::active()->get();
        $isSelfService = false;

        return view('expenses.index', compact('expenseTypes', 'isSelfService'));
    }

    public function indexAjax(Request $request)
    {
        try {
            $columns = [
                1 => 'id',
                2 => 'user',
                3 => 'expenseType',
                4 => 'expenseDate',
                5 => 'amount',
                6 => 'status',
                7 => 'image',
            ];

            $query = ExpenseRequest::query();

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            if ($request->has('employeeFilter') && ! empty($request->input('employeeFilter'))) {
                $query->where('user_id', $request->input('employeeFilter'));
            }

            if ($request->has('dateFilter') && ! empty($request->input('dateFilter'))) {
                $query->whereDate('for_date', $request->input('dateFilter'));
            }

            if ($request->has('expenseTypeFilter') && ! empty($request->input('expenseTypeFilter'))) {
                $query->where('expense_type_id', $request->input('expenseTypeFilter'));
            }

            if ($request->has('statusFilter') && ! empty($request->input('statusFilter'))) {
                $query->where('expense_requests.status', $request->input('statusFilter'));
            }

            $totalData = $query->count();

            if ($order == 'id') {
                $order = 'expense_requests.id';
                $query->orderBy($order, $dir);
            }

            if (empty($request->input('search.value'))) {
                $expenseRequests = $query->select(
                    'expense_requests.*',
                    'user.first_name',
                    'user.last_name',
                    'user.code',
                    'user.profile_picture',
                    'expense_type.name as expense_type_name',
                )
                    ->leftJoin('users as user', 'expense_requests.user_id', '=', 'user.id')
                    ->leftJoin('expense_types as expense_type', 'expense_requests.expense_type_id', '=', 'expense_type.id')
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            } else {
                $search = $request->input('search.value');
                $expenseRequests = $query->select(
                    'expense_requests.*',
                    'user.first_name',
                    'user.last_name',
                    'user.code',
                    'user.profile_picture',
                    'expense_type.name as expense_type_name',
                )
                    ->leftJoin('users as user', 'expense_requests.user_id', '=', 'user.id')
                    ->leftJoin('expense_types as expense_type', 'expense_requests.expense_type_id', '=', 'expense_type.id')
                    ->where(function ($query) use ($search) {
                        $query->where('expense_requests.id', 'LIKE', "%{$search}%")
                            ->orWhere('expense_requests.user_id', 'LIKE', "%{$search}%")
                            ->orWhere('user.first_name', 'LIKE', "%{$search}%")
                            ->orWhere('user.last_name', 'LIKE', "%{$search}%")
                            ->orWhere('user.code', 'LIKE', "%{$search}%")
                            ->orWhere('expense_type.name', 'LIKE', "%{$search}%");
                    })
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            }

            $totalFiltered = $query->count();

            $data = [];
            if (! empty($expenseRequests)) {
                foreach ($expenseRequests as $expenseRequest) {
                    $nestedData['id'] = $expenseRequest->id;
                    $nestedData['user_id'] = $expenseRequest->user_id;
                    $nestedData['for_date'] = $expenseRequest->for_date->format(Constants::DateFormat);
                    $nestedData['expense_type'] = $expenseRequest->expense_type_name;
                    $nestedData['amount'] = $expenseRequest->amount;
                    $nestedData['approved_amount'] = $expenseRequest->approved_amount;
                    $nestedData['document_url'] = $expenseRequest->document_url != null ? asset('storage/'.Constants::BaseFolderExpenseProofs.$expenseRequest->document_url) : null;
                    $nestedData['created_at'] = $expenseRequest->created_at->format(Constants::DateTimeFormat);
                    $nestedData['status'] = $expenseRequest->status;

                    $nestedData['user_name'] = $expenseRequest->user->getFullName();
                    $nestedData['user_code'] = $expenseRequest->user->code;
                    $nestedData['user_profile_image'] = $expenseRequest->user->profile_picture != null ? asset('storage/'.Constants::BaseFolderEmployeeProfileWithSlash.$expenseRequest->user->profile_picture) : null;
                    $nestedData['user_initial'] = $expenseRequest->user->getInitials();

                    $data[] = $nestedData;
                }
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalData,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return Error::response('Something went wrong. Please try again.');
        }
    }

    public function actionAjax(Request $request)
    {

        $validated = $request->validate([
            'id' => 'required|exists:expense_requests,id',
            'status' => 'required|in:approved,rejected',
            'approvedAmount' => 'nullable|numeric|min:0',
            'adminRemarks' => 'nullable|string',
        ]);

        try {
            $expenseRequest = ExpenseRequest::findOrFail($validated['id']);
            $expenseRequest->status = $validated['status'];
            $expenseRequest->admin_remarks = $validated['adminRemarks'];

            // Set approval/rejection metadata
            if ($validated['status'] === 'approved') {
                // Default approved_amount to requested amount if not provided
                $expenseRequest->approved_amount = $validated['approvedAmount'] ?? $expenseRequest->amount;
                $expenseRequest->approved_at = now();
                $expenseRequest->approved_by_id = auth()->id();

                // Validate approved_amount is not zero or negative
                if ($expenseRequest->approved_amount <= 0) {
                    return Error::response('Approved amount must be greater than zero.');
                }
            } elseif ($validated['status'] === 'rejected') {
                $expenseRequest->approved_amount = null; // Clear approved amount on rejection
                $expenseRequest->rejected_at = now();
                $expenseRequest->rejected_by_id = auth()->id();
            }

            $expenseRequest->save();

            Notification::send($expenseRequest->user, new ExpenseRequestApproval($expenseRequest, $validated['status']));

            return Success::response('Expense request '.$validated['status'].' successfully.');
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return Error::response('Something went wrong. Please try again.');
        }
    }

    public function getByIdAjax($id)
    {
        $expenseRequest = ExpenseRequest::findOrFail($id);

        if (! $expenseRequest) {
            return Error::response('Expense request not found.');
        }

        $response = [
            'id' => $expenseRequest->id,
            'userName' => $expenseRequest->user->getFullName(),
            'userCode' => $expenseRequest->user->code,
            'expenseType' => $expenseRequest->expenseType->name,
            'forDate' => $expenseRequest->for_date->format(Constants::DateFormat),
            'amount' => $expenseRequest->amount,
            'approvedAmount' => $expenseRequest->approved_amount,
            'document' => $expenseRequest->document_url != null ? asset('storage/'.Constants::BaseFolderExpenseProofs.$expenseRequest->document_url) : null,
            'status' => $expenseRequest->status,
            'createdAt' => $expenseRequest->created_at->format(Constants::DateTimeFormat),
            'userNotes' => $expenseRequest->remarks,
        ];

        return Success::response($response);
    }

    // ========================================
    // SELF-SERVICE EXPENSE METHODS
    // ========================================

    /**
     * Display logged-in user's expense requests
     */
    public function myExpenses()
    {
        $expenseTypes = ExpenseType::active()->get();
        $settings = Settings::first();

        return view('expenses.my-expenses', compact('expenseTypes', 'settings'));
    }

    /**
     * Get logged-in user's expenses for DataTable
     */
    public function myExpensesAjax(Request $request)
    {
        try {
            $columns = [
                1 => 'id',
                2 => 'expenseType',
                3 => 'expenseDate',
                4 => 'amount',
                5 => 'status',
                6 => 'image',
            ];

            $query = ExpenseRequest::where('user_id', auth()->id());

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')] ?? 'id';
            $dir = $request->input('order.0.dir') ?? 'desc';

            if ($request->has('dateFilter') && ! empty($request->input('dateFilter'))) {
                $query->whereDate('for_date', $request->input('dateFilter'));
            }

            if ($request->has('statusFilter') && ! empty($request->input('statusFilter'))) {
                $query->where('expense_requests.status', $request->input('statusFilter'));
            }

            if ($request->has('expenseTypeFilter') && ! empty($request->input('expenseTypeFilter'))) {
                $query->where('expense_type_id', $request->input('expenseTypeFilter'));
            }

            $totalData = $query->count();

            if ($order == 'id') {
                $order = 'expense_requests.id';
                $query->orderBy($order, $dir);
            }

            if (empty($request->input('search.value'))) {
                $expenseRequests = $query->select(
                    'expense_requests.*',
                    'expense_type.name as expense_type_name',
                )
                    ->leftJoin('expense_types as expense_type', 'expense_requests.expense_type_id', '=', 'expense_type.id')
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            } else {
                $search = $request->input('search.value');
                $expenseRequests = $query->select(
                    'expense_requests.*',
                    'expense_type.name as expense_type_name',
                )
                    ->leftJoin('expense_types as expense_type', 'expense_requests.expense_type_id', '=', 'expense_type.id')
                    ->where(function ($query) use ($search) {
                        $query->where('expense_requests.id', 'LIKE', "%{$search}%")
                            ->orWhere('expense_type.name', 'LIKE', "%{$search}%")
                            ->orWhere('expense_requests.amount', 'LIKE', "%{$search}%");
                    })
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            }

            $totalFiltered = $query->count();

            $data = [];
            if (! empty($expenseRequests)) {
                foreach ($expenseRequests as $expenseRequest) {
                    $nestedData['id'] = $expenseRequest->id;
                    $nestedData['for_date'] = $expenseRequest->for_date->format(Constants::DateFormat);
                    $nestedData['expense_type'] = $expenseRequest->expense_type_name;
                    $nestedData['amount'] = $expenseRequest->amount;
                    $nestedData['approved_amount'] = $expenseRequest->approved_amount;
                    $nestedData['document_url'] = $expenseRequest->document_url != null ? asset('storage/'.Constants::BaseFolderExpenseProofs.$expenseRequest->document_url) : null;
                    $nestedData['created_at'] = $expenseRequest->created_at->format(Constants::DateTimeFormat);
                    $nestedData['status'] = $expenseRequest->status;

                    $data[] = $nestedData;
                }
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalData,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Error in myExpensesAjax: '.$e->getMessage());

            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    /**
     * Show form to create a new expense for logged-in user
     */
    public function createMyExpense()
    {
        $expenseTypes = ExpenseType::active()->get();
        $settings = Settings::first();

        return view('expenses.create', compact('expenseTypes', 'settings'));
    }

    /**
     * Store a new expense for logged-in user
     */
    public function storeMyExpense(Request $request)
    {
        $request->validate([
            'expense_type_id' => 'required|exists:expense_types,id',
            'for_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $expense = new ExpenseRequest;
            $expense->user_id = auth()->id();
            $expense->expense_type_id = $request->input('expense_type_id');
            $expense->for_date = $request->input('for_date');
            $expense->amount = $request->input('amount');
            $expense->remarks = $request->input('remarks');
            $expense->status = 'pending';

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->storeAs(Constants::BaseFolderExpenseProofs, $filename, 'public');
                $expense->document_url = $filename;
            }

            $expense->save();

            return Success::response('Expense request submitted successfully');
        } catch (Exception $e) {
            Log::error('Error creating expense: '.$e->getMessage());

            return Error::response('Failed to create expense request');
        }
    }

    /**
     * Show a specific expense for logged-in user
     */
    public function showMyExpense($id)
    {
        $expense = ExpenseRequest::where('user_id', auth()->id())->findOrFail($id);

        return Success::response([
            'id' => $expense->id,
            'expenseType' => $expense->expenseType->name,
            'forDate' => $expense->for_date->format(Constants::DateFormat),
            'amount' => $expense->amount,
            'approvedAmount' => $expense->approved_amount,
            'document' => $expense->document_url ? asset('storage/'.Constants::BaseFolderExpenseProofs.$expense->document_url) : null,
            'status' => $expense->status,
            'remarks' => $expense->remarks,
            'adminRemarks' => $expense->admin_remarks,
            'createdAt' => $expense->created_at->format(Constants::DateTimeFormat),
        ]);
    }

    /**
     * Show form to edit expense for logged-in user
     */
    public function editMyExpense($id)
    {
        $expense = ExpenseRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $expenseTypes = ExpenseType::active()->get();

        return Success::response([
            'expense' => $expense,
            'expenseTypes' => $expenseTypes,
        ]);
    }

    /**
     * Update expense for logged-in user
     */
    public function updateMyExpense(Request $request, $id)
    {
        $request->validate([
            'expense_type_id' => 'required|exists:expense_types,id',
            'for_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $expense = ExpenseRequest::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->findOrFail($id);

            $expense->expense_type_id = $request->input('expense_type_id');
            $expense->for_date = $request->input('for_date');
            $expense->amount = $request->input('amount');
            $expense->remarks = $request->input('remarks');

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->storeAs(Constants::BaseFolderExpenseProofs, $filename, 'public');
                $expense->document_url = $filename;
            }

            $expense->save();

            return Success::response('Expense request updated successfully');
        } catch (Exception $e) {
            Log::error('Error updating expense: '.$e->getMessage());

            return Error::response('Failed to update expense request');
        }
    }

    /**
     * Delete expense for logged-in user
     */
    public function deleteMyExpense($id)
    {
        try {
            $expense = ExpenseRequest::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->findOrFail($id);

            $expense->delete();

            return Success::response('Expense request deleted successfully');
        } catch (Exception $e) {
            Log::error('Error deleting expense: '.$e->getMessage());

            return Error::response('Failed to delete expense request');
        }
    }

    // ========================================
    // EMPLOYEE EXPENSE REPORT
    // ========================================

    /**
     * Display employee expense report
     */
    public function employeeExpenseReport(Request $request)
    {
        // If it's an AJAX request (DataTable), return JSON data
        if ($request->ajax()) {
            $dataTable = new \App\DataTables\EmployeeExpenseReportDataTable;

            return $dataTable->ajax();
        }

        // Otherwise, return the view
        $settings = Settings::first();
        $expenseTypes = ExpenseType::active()->get();

        // Get departments for filter
        $departments = \App\Models\Department::all();

        return view('expenses.reports.employee-expense', compact('settings', 'expenseTypes', 'departments'));
    }

    /**
     * Get employee expense statistics
     */
    public function getEmployeeExpenseStatistics(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

            $query = ExpenseRequest::whereBetween('for_date', [$dateFrom, $dateTo]);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('employee_id')) {
                $query->where('user_id', $request->employee_id);
            }

            if ($request->filled('department_id')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('team_id', $request->department_id);
                });
            }

            // Calculate statistics
            $totalEmployees = (clone $query)->distinct('user_id')->count('user_id');
            $totalSubmitted = (clone $query)->sum('amount');
            $avgPerEmployee = $totalEmployees > 0 ? $totalSubmitted / $totalEmployees : 0;
            $totalPendingApprovals = (clone $query)->where('status', 'pending')->count();

            // Calculate compliance rate (% with documents)
            $withDocuments = (clone $query)->whereNotNull('document_url')->count();
            $totalRequests = (clone $query)->count();
            $complianceRate = $totalRequests > 0 ? ($withDocuments / $totalRequests) * 100 : 0;

            // Get top 10 employees by total submitted amount
            $topEmployees = ExpenseRequest::select('user_id', DB::raw('SUM(amount) as total_amount'))
                ->whereBetween('for_date', [$dateFrom, $dateTo])
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->filled('department_id'), function ($q) use ($request) {
                    $q->whereHas('user', function ($query) use ($request) {
                        $query->where('team_id', $request->department_id);
                    });
                })
                ->with('user')
                ->groupBy('user_id')
                ->orderByDesc('total_amount')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->user ? $item->user->getFullName() : 'Unknown',
                        'amount' => round($item->total_amount, 2),
                    ];
                });

            // Get monthly trend data (last 6 months)
            $monthlyTrend = ExpenseRequest::selectRaw('DATE_FORMAT(for_date, "%Y-%m") as month, SUM(amount) as total_amount')
                ->where('for_date', '>=', now()->subMonths(6)->startOfMonth())
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->filled('employee_id'), function ($q) use ($request) {
                    $q->where('user_id', $request->employee_id);
                })
                ->when($request->filled('department_id'), function ($q) use ($request) {
                    $q->whereHas('user', function ($query) use ($request) {
                        $query->where('team_id', $request->department_id);
                    });
                })
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => \Carbon\Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                        'amount' => round($item->total_amount, 2),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_employees' => $totalEmployees,
                    'total_submitted' => round($totalSubmitted, 2),
                    'avg_per_employee' => round($avgPerEmployee, 2),
                    'total_pending_approvals' => $totalPendingApprovals,
                    'compliance_rate' => round($complianceRate, 1),
                    'top_employees' => $topEmployees,
                    'monthly_trend' => $monthlyTrend,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching employee expense statistics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }
}
