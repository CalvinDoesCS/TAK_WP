<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Enums\LeaveRequestStatus;
use App\Enums\Status;
use App\Enums\UserAccountStatus;
use App\Helpers\TrackingHelper;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\ExpenseRequest;
use App\Models\LeaveRequest;
use App\Models\Settings;
use App\Models\Team;
use App\Models\User;
use App\Services\AddonService\AddonService;
use Exception;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected AddonService $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Get module model class if module is active
     *
     * @param  string  $moduleName  The module name (e.g., 'FieldManager')
     * @param  string  $modelClass  The full model class name
     * @return string|null Returns model class if module active and class exists, null otherwise
     */
    protected function getModuleModel(string $moduleName, string $modelClass): ?string
    {
        if (! $this->addonService->isAddonEnabled($moduleName)) {
            return null;
        }

        if (! class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }

    public function index()
    {
        $user = auth()->user();

        // Route to appropriate dashboard based on role
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $this->adminDashboard();
        } elseif ($user->hasRole(['hr', 'hr_manager', 'hr_executive'])) {
            return $this->hrDashboard();
        } elseif ($user->hasRole(['employee', 'field_employee', 'office_employee', 'manager', 'team_leader'])) {
            return $this->employeeDashboard();
        }

        // Default to admin dashboard for unknown roles
        return $this->adminDashboard();
    }

    /**
     * Admin Dashboard - Full system overview
     */
    protected function adminDashboard()
    {
        $data = [];

        // Core HR & Attendance Stats
        $data['core'] = $this->getCoreStats();

        // Module Categories - Only fetch if enabled
        $data['hrWorkforce'] = $this->getHRWorkforceStats();
        $data['finance'] = $this->getFinanceStats();
        $data['businessOperations'] = $this->getBusinessOperationsStats();
        $data['sales'] = $this->getSalesStats();
        $data['communications'] = $this->getCommunicationsStats();
        $data['documents'] = $this->getDocumentsStats();
        $data['operations'] = $this->getOperationsStats();
        $data['system'] = $this->getSystemStats();

        // Enabled modules list for frontend
        $data['enabledModules'] = $this->getEnabledModulesList();

        return view('dashboard.index', $data);
    }

    /**
     * HR Dashboard - HR-focused overview
     */
    protected function hrDashboard()
    {
        $data = [];

        // Core HR & Attendance Stats
        $data['core'] = $this->getCoreStats();

        // HR-specific module stats
        $data['hrWorkforce'] = $this->getHRWorkforceStats();
        $data['finance'] = $this->getFinanceStats();
        $data['documents'] = $this->getDocumentsStats();

        // Enabled modules list for frontend
        $data['enabledModules'] = $this->getEnabledModulesList();

        return view('dashboard.hr', $data);
    }

    /**
     * Employee Dashboard - Personal overview
     */
    protected function employeeDashboard()
    {
        $user = auth()->user();
        $today = \Carbon\Carbon::today();
        $currentMonth = \Carbon\Carbon::now()->startOfMonth();

        // Today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // This month's working hours
        $monthlyWorkingMinutes = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [$currentMonth, \Carbon\Carbon::now()])
            ->where('check_out_time', '!=', null)
            ->get()
            ->sum(function ($attendance) {
                return $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
            });

        // This week's working hours
        $weeklyWorkingMinutes = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()])
            ->where('check_out_time', '!=', null)
            ->get()
            ->sum(function ($attendance) {
                return $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
            });

        // Leave statistics
        $totalLeaves = LeaveRequest::where('user_id', $user->id)->count();
        $approvedLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->count();
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', LeaveRequestStatus::PENDING)
            ->count();

        // Task statistics (if FieldTask module is enabled)
        $totalTasks = 0;
        $completedTasks = 0;
        $pendingTasks = 0;
        $recentTasks = collect();

        $taskModel = $this->getModuleModel('FieldTask', \Modules\FieldTask\App\Models\Task::class);
        if ($taskModel) {
            $totalTasks = $taskModel::where('user_id', $user->id)->count();
            $completedTasks = $taskModel::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();
            $pendingTasks = $taskModel::where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->count();
            $recentTasks = $taskModel::where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get();
        }

        // Recent activities
        $recentLeaveRequests = LeaveRequest::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentExpenseRequests = ExpenseRequest::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $addonService = app(AddonService::class);

        return view('employee.dashboard.index', [
            'user' => $user,
            'todayAttendance' => $todayAttendance,
            'monthlyWorkingHours' => round($monthlyWorkingMinutes / 60, 2),
            'weeklyWorkingHours' => round($weeklyWorkingMinutes / 60, 2),
            'totalLeaves' => $totalLeaves,
            'approvedLeaves' => $approvedLeaves,
            'pendingLeaves' => $pendingLeaves,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'pendingTasks' => $pendingTasks,
            'recentLeaveRequests' => $recentLeaveRequests,
            'recentExpenseRequests' => $recentExpenseRequests,
            'recentTasks' => $recentTasks,
            'addonService' => $addonService,
        ]);
    }

    /**
     * Get core HR and attendance statistics
     */
    protected function getCoreStats(): array
    {
        $totalUser = User::count();
        $active = User::where('status', UserAccountStatus::ACTIVE)->count();
        $presentUsersCount = Attendance::whereDate('created_at', now())->count();

        // Use global helper functions for accurate working hours calculation
        $todayHours = getTodayWorkingHours();
        $weeklyHours = getWeeklyWorkingHours();

        $onLeaveUsersCount = LeaveRequest::whereDate('from_date', now())
            ->where('status', LeaveRequestStatus::APPROVED)
            ->count();

        // Check if LoanManagement module is enabled
        $pendingLoanRequests = 0;
        $loanRequestModel = $this->getModuleModel('LoanManagement', \Modules\LoanManagement\App\Models\LoanRequest::class);
        if ($loanRequestModel) {
            $pendingLoanRequests = $loanRequestModel::where('status', 'pending')->count();
        }

        return [
            'totalEmployees' => $totalUser,
            'activeEmployees' => $active,
            'todayPresent' => $presentUsersCount,
            'todayAbsent' => $active - $presentUsersCount,
            'todayOnLeave' => $onLeaveUsersCount,
            'todayHours' => $todayHours, // Already in hours (decimal)
            'weeklyHours' => $weeklyHours, // Already in hours (decimal)
            'pendingLeaveRequests' => LeaveRequest::where('status', 'pending')->count(),
            'pendingExpenseRequests' => ExpenseRequest::where('status', 'pending')->count(),
            'pendingLoanRequests' => $pendingLoanRequests,
        ];
    }

    /**
     * Get HR & Workforce module statistics
     */
    protected function getHRWorkforceStats(): array
    {
        $stats = [];

        // Payroll Module
        if ($this->addonService->isAddonEnabled('Payroll')) {
            $payrollModel = $this->getModuleModel('Payroll', \Modules\Payroll\App\Models\PayrollRecord::class);
            if ($payrollModel) {
                try {
                    $latestPayroll = $payrollModel::latest()->first();
                    $stats['payroll'] = [
                        'enabled' => true,
                        'lastProcessed' => $latestPayroll ? $latestPayroll->created_at->format('M Y') : null,
                        'lastAmount' => $latestPayroll ? $latestPayroll->net_salary ?? 0 : 0,
                        'totalEmployees' => $latestPayroll ? $payrollModel::where('payroll_cycle_id', $latestPayroll->payroll_cycle_id)->count() : 0,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Payroll stats error: '.$e->getMessage());
                }
            }
        }

        // Recruitment Module
        if ($this->addonService->isAddonEnabled('Recruitment')) {
            $jobOpeningModel = $this->getModuleModel('Recruitment', \Modules\Recruitment\App\Models\JobOpening::class);
            $applicationModel = $this->getModuleModel('Recruitment', \Modules\Recruitment\App\Models\JobApplication::class);
            if ($jobOpeningModel && $applicationModel) {
                try {
                    $stats['recruitment'] = [
                        'enabled' => true,
                        'openPositions' => $jobOpeningModel::where('status', 'open')->count(),
                        'totalApplications' => $applicationModel::whereMonth('created_at', now()->month)->count(),
                        'pendingReview' => $applicationModel::where('status', 'pending')->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('Recruitment stats error: '.$e->getMessage());
                }
            }
        }

        // LMS Module
        if ($this->addonService->isAddonEnabled('LMS')) {
            $courseModel = $this->getModuleModel('LMS', \Modules\LMS\App\Models\Course::class);
            $enrollmentModel = $this->getModuleModel('LMS', \Modules\LMS\App\Models\CourseEnrollment::class);
            if ($courseModel && $enrollmentModel) {
                try {
                    $totalEnrollments = $enrollmentModel::count();
                    $completedEnrollments = $enrollmentModel::where('status', 'completed')->count();
                    $stats['lms'] = [
                        'enabled' => true,
                        'activeCourses' => $courseModel::where('status', 'active')->count(),
                        'totalEnrollments' => $totalEnrollments,
                        'completionRate' => $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0,
                    ];
                } catch (\Exception $e) {
                    Log::warning('LMS stats error: '.$e->getMessage());
                }
            }
        }

        // Assets Management Module
        if ($this->addonService->isAddonEnabled('Assets')) {
            $assetModel = $this->getModuleModel('Assets', \Modules\Assets\App\Models\Asset::class);
            if ($assetModel) {
                try {
                    $stats['assets'] = [
                        'enabled' => true,
                        'total' => $assetModel::count(),
                        'assigned' => $assetModel::where('status', 'assigned')->count(),
                        'available' => $assetModel::where('status', 'available')->count(),
                        'maintenance' => $assetModel::where('status', 'maintenance')->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('Assets stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get Finance & Accounting module statistics
     */
    protected function getFinanceStats(): array
    {
        $stats = [];

        // AccountingCore Module (Core Module)
        if ($this->addonService->isAddonEnabled('AccountingCore', true)) {
            $transactionModel = $this->getModuleModel('AccountingCore', \Modules\AccountingCore\App\Models\BasicTransaction::class);
            if ($transactionModel) {
                try {
                    $monthlyIncome = $transactionModel::where('type', 'income')
                        ->whereMonth('transaction_date', now()->month)
                        ->sum('amount');
                    $monthlyExpense = $transactionModel::where('type', 'expense')
                        ->whereMonth('transaction_date', now()->month)
                        ->sum('amount');

                    $stats['accounting'] = [
                        'enabled' => true,
                        'monthlyIncome' => $monthlyIncome,
                        'monthlyExpense' => $monthlyExpense,
                        'netProfit' => $monthlyIncome - $monthlyExpense,
                        'transactionsThisMonth' => $transactionModel::whereMonth('transaction_date', now()->month)->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('AccountingCore stats error: '.$e->getMessage());
                }
            }
        }

        // Loan Management - only if module is enabled
        $loanRequestModel = $this->getModuleModel('LoanManagement', \Modules\LoanManagement\App\Models\LoanRequest::class);
        if ($loanRequestModel) {
            try {
                $stats['loans'] = [
                    'enabled' => true,
                    'pending' => $loanRequestModel::where('status', 'pending')->count(),
                    'approved' => $loanRequestModel::where('status', 'approved')->count(),
                    'totalAmount' => $loanRequestModel::where('status', 'approved')->sum('amount'),
                ];
            } catch (\Exception $e) {
                Log::warning('LoanManagement stats error: '.$e->getMessage());
            }
        }

        // Expense Requests
        $stats['expenses'] = [
            'enabled' => true, // Core feature
            'pending' => ExpenseRequest::where('status', 'pending')->count(),
            'approvedThisMonth' => ExpenseRequest::where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->count(),
            'totalAmountThisMonth' => ExpenseRequest::where('status', 'approved')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        // Payment Collection Module
        if ($this->addonService->isAddonEnabled('PaymentCollection')) {
            $paymentModel = $this->getModuleModel('PaymentCollection', \Modules\PaymentCollection\App\Models\PaymentCollection::class);
            if ($paymentModel) {
                try {
                    $stats['paymentCollection'] = [
                        'enabled' => true,
                        'collectedToday' => $paymentModel::whereDate('collection_date', now())->sum('amount'),
                        'collectedThisMonth' => $paymentModel::whereMonth('collection_date', now()->month)->sum('amount'),
                        'paymentsCount' => $paymentModel::whereMonth('collection_date', now()->month)->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('PaymentCollection stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get Business Operations module statistics (SystemCore)
     */
    protected function getBusinessOperationsStats(): array
    {
        $stats = [];

        // SystemCore Module - Business Operations
        if ($this->addonService->isAddonEnabled('SystemCore')) {
            $customerModel = $this->getModuleModel('SystemCore', \Modules\SystemCore\App\Models\CoreCustomer::class);
            $supplierModel = $this->getModuleModel('SystemCore', \Modules\SystemCore\App\Models\CoreSupplier::class);
            $productModel = $this->getModuleModel('SystemCore', \Modules\SystemCore\App\Models\CoreProduct::class);
            $salesOrderModel = $this->getModuleModel('SystemCore', \Modules\SystemCore\App\Models\CoreSalesOrder::class);
            $purchaseOrderModel = $this->getModuleModel('SystemCore', \Modules\SystemCore\App\Models\CorePurchaseOrder::class);

            if ($customerModel || $supplierModel || $productModel || $salesOrderModel || $purchaseOrderModel) {
                try {
                    $stats['systemCore'] = [
                        'enabled' => true,
                        'totalCustomers' => $customerModel ? $customerModel::count() : 0,
                        'activeCustomers' => $customerModel ? $customerModel::active()->count() : 0,
                        'totalSuppliers' => $supplierModel ? $supplierModel::count() : 0,
                        'activeSuppliers' => $supplierModel ? $supplierModel::active()->count() : 0,
                        'totalProducts' => $productModel ? $productModel::count() : 0,
                        'activeProducts' => $productModel ? $productModel::active()->count() : 0,
                        'totalSalesOrders' => $salesOrderModel ? $salesOrderModel::count() : 0,
                        'salesOrdersToday' => $salesOrderModel ? $salesOrderModel::whereDate('created_at', now())->count() : 0,
                        'salesThisMonth' => $salesOrderModel ? $salesOrderModel::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount') : 0,
                        'totalPurchaseOrders' => $purchaseOrderModel ? $purchaseOrderModel::count() : 0,
                        'purchaseOrdersToday' => $purchaseOrderModel ? $purchaseOrderModel::whereDate('created_at', now())->count() : 0,
                    ];
                } catch (\Exception $e) {
                    Log::warning('SystemCore stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get Sales & Field Operations module statistics
     */
    protected function getSalesStats(): array
    {
        $stats = [];

        // Field Manager Module
        if ($this->addonService->isAddonEnabled('FieldManager')) {
            $visitModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class);
            $clientModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Client::class);
            if ($visitModel && $clientModel) {
                try {
                    $stats['fieldManager'] = [
                        'enabled' => true,
                        'visitsToday' => $visitModel::whereDate('created_at', now())->count(),
                        'visitsThisWeek' => $visitModel::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                        'totalClients' => $clientModel::count(),
                        'activeClients' => $clientModel::where('status', 'active')->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('FieldManager stats error: '.$e->getMessage());
                }
            }
        }

        // Field Task Module
        if ($this->addonService->isAddonEnabled('FieldTask')) {
            $taskModel = $this->getModuleModel('FieldTask', \Modules\FieldTask\App\Models\Task::class);
            if ($taskModel) {
                try {
                    $stats['fieldTask'] = [
                        'enabled' => true,
                        'newTasks' => $taskModel::where('status', 'new')->count(),
                        'inProgress' => $taskModel::where('status', 'in_progress')->count(),
                        'completedToday' => $taskModel::where('status', 'completed')->whereDate('updated_at', now())->count(),
                        'overdue' => $taskModel::where('status', '!=', 'completed')
                            ->where('due_date', '<', now())
                            ->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('FieldTask stats error: '.$e->getMessage());
                }
            }
        }

        // Product Order Module
        if ($this->addonService->isAddonEnabled('ProductOrder')) {
            $orderModel = $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class);
            if ($orderModel) {
                try {
                    $stats['productOrder'] = [
                        'enabled' => true,
                        'ordersToday' => $orderModel::whereDate('created_at', now())->count(),
                        'ordersThisMonth' => $orderModel::whereMonth('created_at', now()->month)->count(),
                        'totalValueThisMonth' => $orderModel::whereMonth('created_at', now()->month)->sum('total'),
                        'pendingOrders' => $orderModel::where('status', 'pending')->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('ProductOrder stats error: '.$e->getMessage());
                }
            }
        }

        // Sales Target Module
        if ($this->addonService->isAddonEnabled('SalesTarget')) {
            $targetModel = $this->getModuleModel('SalesTarget', \Modules\SalesTarget\App\Models\SalesTarget::class);
            if ($targetModel) {
                try {
                    $currentMonthTargets = $targetModel::whereMonth('start_date', now()->month)
                        ->whereYear('start_date', now()->year)
                        ->get();

                    $totalTarget = $currentMonthTargets->sum('target_amount');
                    $totalAchieved = $currentMonthTargets->sum('achieved_amount');
                    $achievementPercentage = $totalTarget > 0 ? round(($totalAchieved / $totalTarget) * 100, 1) : 0;

                    $stats['salesTarget'] = [
                        'enabled' => true,
                        'currentMonthTarget' => $totalTarget,
                        'achieved' => $totalAchieved,
                        'achievementPercentage' => $achievementPercentage,
                        'remainingTarget' => max(0, $totalTarget - $totalAchieved),
                    ];
                } catch (\Exception $e) {
                    Log::warning('SalesTarget stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get Communications module statistics
     */
    protected function getCommunicationsStats(): array
    {
        $stats = [];

        // Agora Call Module (uses CallLog from OCConnect module)
        if ($this->addonService->isAddonEnabled('AgoraCall')) {
            $callLogModel = $this->getModuleModel('OCConnect', \Modules\OCConnect\App\Models\CallLog::class);
            if ($callLogModel) {
                try {
                    $stats['agoraCall'] = [
                        'enabled' => true,
                        'callsToday' => $callLogModel::where('provider', 'agora')->whereDate('created_at', now())->count(),
                        'missedCallsToday' => $callLogModel::where('provider', 'agora')->where('status', 'missed')->whereDate('created_at', now())->count(),
                        'totalDurationToday' => $callLogModel::where('provider', 'agora')->whereDate('created_at', now())
                            ->where('status', 'completed')
                            ->sum('duration'),
                    ];
                } catch (\Exception $e) {
                    Log::warning('AgoraCall stats error: '.$e->getMessage());
                }
            }
        }

        // Notice Board Module
        if ($this->addonService->isAddonEnabled('NoticeBoard')) {
            $noticeModel = $this->getModuleModel('NoticeBoard', \Modules\NoticeBoard\App\Models\Notice::class);
            if ($noticeModel) {
                try {
                    $stats['noticeBoard'] = [
                        'enabled' => true,
                        'activeNotices' => $noticeModel::where('status', 'active')->count(),
                        'recentNotices' => $noticeModel::whereDate('created_at', '>=', now()->subDays(7))->count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('NoticeBoard stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get Documents & Data Management module statistics
     */
    protected function getDocumentsStats(): array
    {
        $stats = [];

        // Document Management Module
        if ($this->addonService->isAddonEnabled('DocumentManagement')) {
            $documentModel = $this->getModuleModel('DocumentManagement', \Modules\DocumentManagement\App\Models\EmployeeDocument::class);
            $documentRequestModel = $this->getModuleModel('DocumentManagement', \Modules\DocumentManagement\App\Models\DocumentRequest::class);
            if ($documentModel) {
                try {
                    $stats['documentManagement'] = [
                        'enabled' => true,
                        'totalDocuments' => $documentModel::count(),
                        'uploadedThisMonth' => $documentModel::whereMonth('created_at', now()->month)->count(),
                        'pendingRequests' => $documentRequestModel ? $documentRequestModel::where('status', 'pending')->count() : 0,
                    ];
                } catch (\Exception $e) {
                    Log::warning('DocumentManagement stats error: '.$e->getMessage());
                }
            }
        }

        // Form Builder Module
        if ($this->addonService->isAddonEnabled('FormBuilder')) {
            try {
                $stats['formBuilder'] = [
                    'enabled' => true,
                    'activeForms' => \Modules\FormBuilder\App\Models\Form::where('is_active', true)->count(),
                    'submissionsToday' => \Modules\FormBuilder\App\Models\FormSubmission::whereDate('created_at', now())->count(),
                    'submissionsThisWeek' => \Modules\FormBuilder\App\Models\FormSubmission::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ];
            } catch (\Exception $e) {
                Log::warning('FormBuilder stats error: '.$e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Get Operations module statistics
     */
    protected function getOperationsStats(): array
    {
        $stats = [];

        // Calendar Module
        if ($this->addonService->isAddonEnabled('Calendar')) {
            $eventModel = $this->getModuleModel('Calendar', \Modules\Calendar\App\Models\Event::class);
            if ($eventModel) {
                try {
                    // Try common date column names
                    $dateColumn = 'date'; // Try 'date' first, then 'start_date'

                    $stats['calendar'] = [
                        'enabled' => true,
                        'eventsToday' => $eventModel::whereDate($dateColumn, now())->count(),
                        'upcomingEvents' => $eventModel::where($dateColumn, '>', now())
                            ->where($dateColumn, '<=', now()->addDays(7))
                            ->count(),
                    ];
                } catch (\Exception $e) {
                    // If date column fails, just show enabled with no data
                    $stats['calendar'] = [
                        'enabled' => true,
                        'eventsToday' => 0,
                        'upcomingEvents' => 0,
                    ];
                }
            }
        }

        // Notes Module
        if ($this->addonService->isAddonEnabled('Notes')) {
            $noteModel = $this->getModuleModel('Notes', \Modules\Notes\App\Models\Note::class);
            if ($noteModel) {
                try {
                    $stats['notes'] = [
                        'enabled' => true,
                        'totalNotes' => $noteModel::count(),
                        'recentNotes' => $noteModel::whereDate('created_at', '>=', now()->subDays(7))->count(),
                    ];
                } catch (\Exception $e) {
                    $stats['notes'] = [
                        'enabled' => true,
                        'totalNotes' => 0,
                        'recentNotes' => 0,
                    ];
                }
            }
        }

        return $stats;
    }

    /**
     * Get System module statistics
     */
    protected function getSystemStats(): array
    {
        $stats = [];

        // System Backup Module
        if ($this->addonService->isAddonEnabled('SystemBackup')) {
            $backupModel = $this->getModuleModel('SystemBackup', \Modules\SystemBackup\App\Models\SystemBackup::class);
            if ($backupModel) {
                try {
                    $latestBackup = $backupModel::latest()->first();
                    $stats['systemBackup'] = [
                        'enabled' => true,
                        'lastBackup' => $latestBackup ? $latestBackup->created_at->diffForHumans() : null,
                        'lastBackupSize' => $latestBackup ? $latestBackup->file_size ?? 0 : 0,
                        'totalBackups' => $backupModel::count(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('SystemBackup stats error: '.$e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Get list of enabled modules for frontend
     */
    protected function getEnabledModulesList(): array
    {
        $modules = [
            'Payroll', 'Recruitment', 'LMS', 'Assets', 'AccountingCore',
            'PaymentCollection', 'FieldManager', 'FieldTask', 'ProductOrder',
            'SalesTarget', 'AgoraCall', 'NoticeBoard', 'DocumentManagement',
            'FormBuilder', 'Calendar', 'Notes', 'SystemBackup', 'SystemCore',
        ];

        $enabled = [];
        foreach ($modules as $module) {
            $isCoreModule = $module === 'AccountingCore';
            if ($this->addonService->isAddonEnabled($module, $isCoreModule)) {
                $enabled[] = $module;
            }
        }

        return $enabled;
    }

    public function getRecentActivities()
    {
        $activities = collect();

        // Fetch Orders if ProductOrder module is active
        $orderModel = $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class);
        $orders = collect();
        if ($orderModel) {
            $orders = $orderModel::with('user')
                ->select('id', 'order_no', 'user_id', 'created_at')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'title' => $order->order_no,
                        'created_at_human' => $order->created_at->diffForHumans(),
                        'created_at' => $order->created_at,
                        'type' => 'Order',
                        'user' => $order->user ? $order->user->getUserForProfile() : 'N/A',
                    ];
                });
        }

        // Fetch Visits if FieldManager module is active
        $visitModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class);
        $visits = collect();
        if ($visitModel) {
            $visits = $visitModel::with('client')
                ->with('createdBy')
                ->select('id', 'client_id', 'created_by_id', 'created_at')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'title' => $visit->client->name ?? 'No Client Name',
                        'created_at_human' => $visit->created_at->diffForHumans(),
                        'created_at' => $visit->created_at,
                        'type' => 'Visit',
                        'user' => $visit->createdBy ? $visit->createdBy->getUserForProfile() : 'N/A',
                    ];
                });
        }

        // Fetch Form Submissions if FormBuilder module is active
        $forms = collect();
        if ($this->addonService->isAddonEnabled('FormBuilder')) {
            try {
                $forms = \Modules\FormBuilder\App\Models\FormSubmission::with('form')
                    ->with('user')
                    ->select('id', 'form_id', 'created_at')
                    ->latest('created_at')
                    ->limit(10)
                    ->get()
                    ->map(function ($form) {
                        return [
                            'id' => $form->id,
                            'title' => $form->form->name ?? 'No Form Name',
                            'created_at_human' => $form->created_at->diffForHumans(),
                            'created_at' => $form->created_at,
                            'type' => 'Form Submission',
                        ];
                    });
            } catch (\Exception $e) {
                Log::warning('Failed to fetch form entries: '.$e->getMessage());
                $forms = collect();
            }
        }

        // Fetch Tasks if FieldTask module is active
        $taskModel = $this->getModuleModel('FieldTask', \Modules\FieldTask\App\Models\Task::class);
        $tasks = collect();
        if ($taskModel) {
            $tasks = $taskModel::with('user')
                ->select('id', 'title', 'user_id', 'created_at')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'created_at_human' => $task->created_at->diffForHumans(),
                        'created_at' => $task->created_at,
                        'type' => 'Task',
                        'user' => $task->user ? $task->user->getUserForProfile() : 'N/A',
                    ];
                });
        }

        // Merge all collections and sort them by created_at
        $activities = $activities
            ->merge($orders)
            ->merge($visits)
            ->merge($forms)
            ->merge($tasks)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return Success::response($activities);
    }

    public function liveLocationView()
    {
        $settings = Settings::select('center_latitude', 'center_longitude', 'map_zoom_level', 'map_api_key')
            ->first();

        return view('dashboard.live_location_view', compact('settings'));
    }

    public function liveLocationAjax()
    {
        try {
            $todayAttendances = Attendance::with(['user.userDevice', 'user.designation'])
                ->whereDate('created_at', now())
                ->get();

            $response = [];
            $trackingHelper = new TrackingHelper;

            foreach ($todayAttendances as $attendance) {
                if (! $attendance->user || ! $attendance->user->userDevice) {
                    continue;
                }

                $userDevice = $attendance->user->userDevice;
                $status = $trackingHelper->isUserOnline($userDevice->updated_at) ? 'online' : 'offline';

                $response[] = [
                    'id' => $attendance->user_id,
                    'name' => $attendance->user->getFullName(),
                    'initials' => $attendance->user->getInitials(),
                    'code' => $attendance->user->code,
                    'profilePicture' => $attendance->user->getProfilePicture(),
                    'designation' => $attendance->user->designation?->name ?? 'N/A',
                    'latitude' => $userDevice->latitude,
                    'longitude' => $userDevice->longitude,
                    'status' => $status,
                    'updatedAt' => $userDevice->updated_at->diffForHumans(),
                ];
            }

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Live location fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Unable to fetch live locations'], 500);
        }
    }

    public function cardView()
    {
        $teamsList = Team::where('status', 'active')
            ->get();

        $attendances = Attendance::whereDate('created_at', now())
            ->with('attendanceLogs')
            ->get();

        $trackingHelper = new TrackingHelper;

        $users = User::where('status', '=', 'active')
            ->where('team_id', '!=', null)
            ->where('shift_id', '!=', null)
            ->get();

        $userDevices = \Modules\FieldManager\App\Models\UserDevice::whereIn('user_id', $users->pluck('id'))
            ->get();

        $teams = [];
        $totalOnline = 0;
        $totalOffline = 0;
        $totalCheckedIn = 0;

        foreach ($teamsList as $team) {

            $user = $users->where('team_id', '=', $team->id);

            $teamAttendances = $attendances->whereIn('user_id', $user->pluck('id'));

            $cardItems = [];

            foreach ($teamAttendances as $attendance) {

                $device = $userDevices
                    ->where('user_id', '=', $attendance->user_id)
                    ->first();

                if ($device == null || $attendance->attendanceLogs->count() == 0 || $attendance->isCheckedOut()) {
                    continue;
                }

                $attendanceLogIds = $attendance->attendanceLogs->pluck('id');

                $isOnline = $trackingHelper->isUserOnline($device->updated_at);

                // Count online/offline for statistics
                if ($isOnline) {
                    $totalOnline++;
                } else {
                    $totalOffline++;
                }
                $totalCheckedIn++;

                // Fetch visits count if FieldSales module is active
                $visitModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class);
                $visitsCount = $visitModel ? $visitModel::whereIn('attendance_log_id', $attendanceLogIds)->count() : 0;

                // Fetch orders count if ProductOrder module is active
                $orderModel = $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class);
                $ordersCount = $orderModel ? $orderModel::whereIn('attendance_log_id', $attendanceLogIds)->count() : 0;

                // Fetch forms filled count if FormBuilder module is active
                $formsFilled = 0;
                if ($this->addonService->isAddonEnabled('FormBuilder')) {
                    try {
                        $formsFilled = \Modules\FormBuilder\App\Models\FormSubmission::where('user_id', $attendance->user_id)
                            ->whereDate('created_at', now())
                            ->count();
                    } catch (\Exception $e) {
                        $formsFilled = 0;
                    }
                }

                $cardItems[] = [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->getFullName(),
                    'initials' => $attendance->user->getInitials(),
                    'profilePicture' => $attendance->user->getProfilePicture(),
                    'employeeCode' => $attendance->user->code,
                    'phoneNumber' => $attendance->user->phone,
                    'batteryLevel' => $device->battery_percentage,
                    'isGpsOn' => $device->is_gps_on,
                    'isWifiOn' => $device->is_wifi_on,
                    'updatedAt' => $device->updated_at->diffForHumans(),
                    'isOnline' => $isOnline,
                    'teamId' => $attendance->user->team_id,
                    'teamName' => $team->name,
                    'attendanceInAt' => $attendance->check_in_time,
                    'attendanceOutAt' => $attendance->check_out_time,
                    'latitude' => $device->latitude,
                    'longitude' => $device->longitude,
                    'address' => $device->address,
                    'visitsCount' => $visitsCount,
                    'ordersCount' => $ordersCount,
                    'formsFilled' => $formsFilled,
                    'attendanceDuration' => $attendance->check_in_time && $attendance->check_out_time ?
                      $attendance->check_in_time->diff($attendance->check_out_time)->format('%H:%I:%S') : 'N/A',
                ];
            }

            if ($user->count() > 0) {

                $teams[] = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'totalEmployees' => $user->count(),
                    'cardItems' => $cardItems,
                ];
            }
        }

        return view('dashboard.card_view', compact('teams', 'totalCheckedIn', 'totalOnline', 'totalOffline'));
    }

    public function cardViewAjax()
    {
        $teamsList = Team::where('status', '=', 'active')
            ->get();

        $attendances = Attendance::whereDate('created_at', '=', now())
            ->with('attendanceLogs')
            ->get();

        $trackingHelper = new TrackingHelper;

        $users = User::where('status', '=', 'active')
            ->where('team_id', '!=', null)
            ->where('shift_id', '!=', null)
            ->get();

        $userDevices = \Modules\FieldManager\App\Models\UserDevice::whereIn('user_id', $users->pluck('id'))
            ->get();

        $cardItems = [];
        $totalOnline = 0;
        $totalOffline = 0;
        $totalCheckedIn = 0;

        foreach ($teamsList as $team) {

            $user = $users->where('team_id', '=', $team->id);

            $teamAttendances = $attendances->whereIn('user_id', $user->pluck('id'));

            foreach ($teamAttendances as $attendance) {

                $device = $userDevices
                    ->where('user_id', '=', $attendance->user_id)
                    ->first();

                if ($device == null || $attendance->attendanceLogs->count() == 0 || $attendance->isCheckedOut()) {
                    continue;
                }

                $isOnline = $trackingHelper->isUserOnline($device->updated_at);

                // Count online/offline for statistics
                if ($isOnline) {
                    $totalOnline++;
                } else {
                    $totalOffline++;
                }
                $totalCheckedIn++;

                $attendanceLogIds = $attendance->attendanceLogs->pluck('id');

                // Fetch visits count if FieldSales module is active
                $visitModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class);
                $visitsCount = $visitModel ? $visitModel::whereIn('attendance_log_id', $attendanceLogIds)->count() : 0;

                // Fetch orders count if ProductOrder module is active
                $orderModel = $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class);
                $ordersCount = $orderModel ? $orderModel::whereIn('attendance_log_id', $attendanceLogIds)->count() : 0;

                // Fetch forms filled count if FormBuilder module is active
                $formsFilled = 0;
                if ($this->addonService->isAddonEnabled('FormBuilder')) {
                    try {
                        $formsFilled = \Modules\FormBuilder\App\Models\FormSubmission::where('user_id', $attendance->user_id)
                            ->whereDate('created_at', now())
                            ->count();
                    } catch (\Exception $e) {
                        $formsFilled = 0;
                    }
                }

                $cardItems[] = [
                    'id' => $attendance->user->id,
                    'name' => $attendance->user->getFullName(),
                    'initials' => $attendance->user->getInitials(),
                    'profilePicture' => $attendance->user->getProfilePicture(),
                    'employeeCode' => $attendance->user->code,
                    'phoneNumber' => $attendance->user->phone,
                    'batteryLevel' => $device->battery_percentage,
                    'isGpsOn' => $device->is_gps_on,
                    'isWifiOn' => $device->is_wifi_on,
                    'updatedAt' => $device->updated_at->diffForHumans(),
                    'isOnline' => $isOnline,
                    'teamId' => $attendance->user->team_id,
                    'teamName' => $team->name,
                    'attendanceInAt' => $attendance->check_in_time,
                    'attendanceOutAt' => $attendance->check_out_time ?? '',
                    'latitude' => $device->latitude,
                    'longitude' => $device->longitude,
                    'address' => $device->address,
                    'visitsCount' => $visitsCount,
                    'ordersCount' => $ordersCount,
                    'formsFilled' => $formsFilled,
                ];
            }
        }

        return response()->json([
            'employees' => $cardItems,
            'statistics' => [
                'totalCheckedIn' => $totalCheckedIn,
                'totalOnline' => $totalOnline,
                'totalOffline' => $totalOffline,
            ],
        ]);
    }

    public function timelineView()
    {
        $employees = User::where('status', UserAccountStatus::ACTIVE)
            ->get();

        $settings = Settings::select('center_latitude', 'center_longitude', 'map_zoom_level', 'map_api_key')
            ->first();

        return view('dashboard.timeline_view', [
            'employees' => $employees,
            'settings' => $settings,
        ]);
    }

    public function getDeviceLocationAjax($userId, $date, $attendanceLogId = null)
    {
        $logs = \Modules\FieldManager\App\Models\DeviceStatusLog::query()
            ->where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'asc');

        if ($attendanceLogId && $attendanceLogId != 'null') {
            $attendanceLog = AttendanceLog::find($attendanceLogId);
            $nextCheckOutLog = AttendanceLog::where('attendance_id', $attendanceLog->attendance_id)
                ->where('created_at', '>', $attendanceLog->created_at)
                ->where('type', 'check_out')
                ->first();

            if (! $attendanceLog) {
                return Error::response('Attendance log not found');
            }

            if ($nextCheckOutLog) {
                $logs = $logs->where('created_at', '>=', $attendanceLog->created_at)
                    ->where('created_at', '<=', $nextCheckOutLog->created_at);
            } else {
                $logs->where('created_at', '>=', $attendanceLog->created_at);
            }
        }

        $logs = $logs->get();

        $trackingHelper = new TrackingHelper;

        $filteredLogs = $trackingHelper->getFilteredLocationPoints($logs);

        $response = [];

        foreach ($filteredLogs['filteredPoints'] as $log) {
            $response[] = [
                'latitude' => $log->latitude,
                'longitude' => $log->longitude,
                'address' => $log->address,
                'created_at' => $log->created_at->format(Constants::TimeFormat),
            ];
        }

        $result = [
            'logs' => $response,
            'rawLogs' => $logs,
            'totalTravelledDistance' => $filteredLogs['totalTravelledDistance'],
            'averageTravelledSpeed' => $filteredLogs['averageTravelledSpeed'],
        ];

        return Success::response($result);
    }

    public function getAttendanceLogAjax($userId, $date)
    {
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->first();

        if (! $attendance) {
            return Success::response([]);
        }

        // Fetch all check-in and check-out logs
        $checkInLogs = AttendanceLog::where('attendance_id', $attendance->id)
            ->where('type', 'check_in')
            ->orderBy('created_at', 'asc')
            ->get();

        $checkOutLogs = AttendanceLog::where('attendance_id', $attendance->id)
            ->where('type', 'check_out')
            ->orderBy('created_at', 'asc')
            ->get();

        // Create session pairs (check-in + matching check-out)
        $sessions = [];
        $sessionNumber = 1;

        foreach ($checkInLogs as $checkInLog) {
            // Find the next check-out log after this check-in
            $checkOutLog = $checkOutLogs
                ->where('created_at', '>', $checkInLog->created_at)
                ->sortBy('created_at')
                ->first();

            $checkInTimeFormatted = $checkInLog->created_at->format(Constants::TimeFormat);

            $duration = null;
            $durationFormatted = 'Ongoing';
            $checkOutTimeFormatted = 'Ongoing';

            if ($checkOutLog) {
                $duration = $checkInLog->created_at->diffInMinutes($checkOutLog->created_at);
                $hours = floor($duration / 60);
                $minutes = $duration % 60;
                $durationFormatted = sprintf('%dh %dm', $hours, $minutes);
                $checkOutTimeFormatted = $checkOutLog->created_at->format(Constants::TimeFormat);
            }

            $sessionLabel = sprintf(
                'Session %d: %s - %s (%s)',
                $sessionNumber,
                $checkInTimeFormatted,
                $checkOutTimeFormatted,
                $durationFormatted
            );

            $sessions[] = [
                'id' => $checkInLog->id,
                'session_number' => $sessionNumber,
                'check_in_time' => $checkInTimeFormatted,
                'check_out_time' => $checkOutLog ? $checkOutTimeFormatted : null,
                'check_in_log_id' => $checkInLog->id,
                'check_out_log_id' => $checkOutLog?->id,
                'duration_minutes' => $duration,
                'duration_formatted' => $durationFormatted,
                'session_label' => $sessionLabel,
                'latitude' => $checkInLog->latitude,
                'longitude' => $checkInLog->longitude,
                'address' => $checkInLog->address,
            ];

            $sessionNumber++;
        }

        return Success::response($sessions);
    }

    public function getActivityAjax($userId, $date, $attendanceLogId = null)
    {
        $employeeId = $userId;

        $trackingHelper = new TrackingHelper;

        $attendance = Attendance::where('user_id', $employeeId)
            ->whereDate('created_at', $date)
            ->first();

        if (! $attendance) {
            return Success::response([]);
        }

        $activities = [];
        if ($attendanceLogId && $attendanceLogId != 'null') {
            $attendanceLog = AttendanceLog::find($attendanceLogId);
            if (! $attendanceLog) {
                return Success::response([]);
            }

            $nextCheckOutLog = AttendanceLog::where('attendance_id', $attendance->id)
                ->where('created_at', '>', $attendanceLog->created_at)
                ->where('type', 'check_out')
                ->first();

            if (! $nextCheckOutLog) {
                $activities = \Modules\FieldManager\App\Models\Activity::where('created_at', '>=', $attendanceLog->created_at)
                    ->where('created_by_id', $employeeId)
                    ->get();
            } else {
                // Filter activities from this log to next log created_at
                $activities = \Modules\FieldManager\App\Models\Activity::where('created_at', '>=', $attendanceLog->created_at)
                    ->where('created_at', '<=', $nextCheckOutLog->created_at)
                    ->where('created_by_id', $employeeId)
                    ->get();
            }
        } else {

            $activities = \Modules\FieldManager\App\Models\Activity::whereDate('created_at', $date)
                ->where('created_by_id', $employeeId)
                ->get();
        }

        if ($activities->count() == 0) {
            return Success::response([]);
        }

        // $activities = $activities->where('accuracy', '>', 20)->toArray();

        // return Success::response($activities);

        $filteredTrackings = $trackingHelper->getFilteredDataV2($activities);

        $timeLineItems = [];

        for ($i = 0; $i < count($filteredTrackings); $i++) {

            $elapseTime = '0';

            $tracking = $filteredTrackings[$i];
            $nextTracking = null;
            if ($tracking->type == 'checked_in') {
                if ($i < count($filteredTrackings) - 1 && count($filteredTrackings) != 1) {
                    $nextTracking = $filteredTrackings[$i + 1];
                    $elapseTime = $tracking->created_at->diff($nextTracking->created_at)->format('%H:%I:%S');
                } else {
                    $elapseTime = '0';
                }
                $timeLineItems[] = [
                    'id' => $tracking->id,
                    'type' => 'checkIn',
                    'accuracy' => $tracking->accuracy,
                    'activity' => $tracking->activity,
                    'batteryPercentage' => $tracking->battery_percentage,
                    'isGPSOn' => $tracking->is_gps_on,
                    'isWifiOn' => $tracking->is_wifi_on,
                    'latitude' => $tracking->latitude,
                    'longitude' => $tracking->longitude,
                    'address' => $tracking->address,
                    'signalStrength' => $tracking->signal_strength,
                    'trackingType' => $tracking->type,
                    'startTime' => $tracking->created_at->format('h:i A'),
                    'endTime' => $nextTracking != null ? $nextTracking->created_at->format('h:i A') : $tracking->created_at->format('h:i A'),
                    'elapseTime' => $elapseTime,
                ];

                continue;
            }

            if ($tracking->type == 'checked_out') {
                $elapseTime = $tracking->created_at->format('%H:%I:%S');

                $timeLineItems[] = [
                    'id' => $tracking->id,
                    'type' => 'checkOut',
                    'accuracy' => $tracking->accuracy,
                    'activity' => $tracking->activity,
                    'batteryPercentage' => $tracking->battery_percentage,
                    'isGPSOn' => $tracking->is_gps_on,
                    'isWifiOn' => $tracking->is_wifi_on,
                    'latitude' => $tracking->latitude,
                    'longitude' => $tracking->longitude,
                    'address' => $tracking->address,
                    'signalStrength' => $tracking->signal_strength,
                    'trackingType' => $tracking->type,
                    'startTime' => $elapseTime,
                    'endTime' => $tracking->created_at->format('h:i A'),
                    'elapseTime' => $elapseTime,
                ];

                continue;
            }

            $nextTracking = null;

            if ($i + 1 < count($filteredTrackings)) {
                $nextTracking = $filteredTrackings[$i + 1];
                $elapseTime = $tracking->created_at->diff($nextTracking->created_at)->format('%H:%I:%S');
            } else {
                $elapseTime = $tracking->created_at->format('%H:%I:%S');
            }

            // Map activity type to user-friendly names
            $activityLabel = match ($tracking->activity) {
                'ActivityType.STILL' => 'STILL',
                'ActivityType.WALKING' => 'WALKING',
                'ActivityType.IN_VEHICLE' => 'IN_VEHICLE',
                default => 'UNKNOWN'
            };

            switch ($tracking->activity) {
                case 'ActivityType.STILL':
                    $timeLineItems[] = [
                        'id' => $tracking->id,
                        'type' => 'still',
                        'accuracy' => $tracking->accuracy ?? 0,
                        'activity' => $tracking->activity,
                        'activityLabel' => $activityLabel,
                        'batteryPercentage' => $tracking->battery_percentage,
                        'isGPSOn' => $tracking->is_gps_on,
                        'isWifiOn' => $tracking->is_wifi_on,
                        'latitude' => $tracking->latitude,
                        'longitude' => $tracking->longitude,
                        'address' => $tracking->address,
                        'signalStrength' => $tracking->signal_strength,
                        'trackingType' => $tracking->type,
                        'startTime' => $tracking->created_at->format('h:i A'),
                        'endTime' => $nextTracking != null ? $nextTracking->created_at->format('h:i A') : $tracking->created_at->format('h:i A'),
                        'elapseTime' => $elapseTime,
                    ];
                    break;
                case 'ActivityType.WALKING':
                    $timeLineItems[] = [
                        'id' => $tracking->id,
                        'type' => 'walk',
                        'accuracy' => $tracking->accuracy ?? 0,
                        'activity' => $tracking->activity,
                        'activityLabel' => $activityLabel,
                        'batteryPercentage' => $tracking->battery_percentage,
                        'isGPSOn' => $tracking->is_gps_on,
                        'isWifiOn' => $tracking->is_wifi_on,
                        'latitude' => $tracking->latitude,
                        'longitude' => $tracking->longitude,
                        'address' => $tracking->address,
                        'signalStrength' => $tracking->signal_strength,
                        'trackingType' => $tracking->type,
                        'startTime' => $tracking->created_at->format('h:i A'),
                        'endTime' => $nextTracking ? $nextTracking->created_at->format('h:i A') : $tracking->created_at->format('h:i A'),
                        'elapseTime' => $elapseTime,
                    ];
                    break;
                default:

                    $distance = 0;
                    if ($i + 1 < count($filteredTrackings)) {
                        $nextTracking = $filteredTrackings[$i + 1];
                    }

                    $timeLineItems[] = [
                        'id' => $tracking->id,
                        'type' => 'vehicle',
                        'accuracy' => $tracking->accuracy ?? 0,
                        'activity' => $tracking->activity,
                        'activityLabel' => $activityLabel,
                        'batteryPercentage' => $tracking->battery_percentage,
                        'isGPSOn' => $tracking->is_gps_on,
                        'isWifiOn' => $tracking->is_wifi_on,
                        'latitude' => $tracking->latitude,
                        'longitude' => $tracking->longitude,
                        'address' => $tracking->address,
                        'signalStrength' => $tracking->signal_strength,
                        'trackingType' => $tracking->type,
                        'startTime' => $tracking->created_at->format('h:i A'),
                        'endTime' => $nextTracking ? $nextTracking->created_at->format('h:i A') : $tracking->created_at->format('h:i A'),
                        'elapseTime' => $elapseTime,
                        'distance' => $distance,
                    ];
                    break;
            }
        }

        return Success::response($timeLineItems);
    }

    public function getStatsForTimeLineAjax($userId, $date, $attendanceLogId = null)
    {
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->with('attendanceLogs')
            ->first();

        if (! $attendance) {
            // Return empty data structure instead of error - no attendance is a valid state
            $user = User::find($userId);

            return Success::response([
                'userId' => $userId,
                'date' => $date,
                'attendanceDuration' => 'N/A',
                'attendanceLogId' => null,
                'name' => $user ? $user->getFullName() : 'Unknown',
                'code' => $user?->code ?? 'N/A',
                'designation' => $user?->designation?->name ?? 'N/A',
                'visits' => collect(),
                'breaks' => collect(),
                'orders' => collect(),
                'tasks' => collect(),
                'visitsCount' => 0,
                'breaksCount' => 0,
                'ordersCount' => 0,
                'tasksCount' => 0,
                'modules' => [
                    'fieldSales' => $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class) !== null,
                    'productOrder' => $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class) !== null,
                    'fieldTask' => $this->getModuleModel('FieldTask', \Modules\FieldTask\App\Models\Task::class) !== null,
                ],
            ]);
        }

        $attendanceLogIds = $attendance->attendanceLogs->pluck('id');

        if ($attendanceLogId && $attendanceLogId != 'null') {
            $attendanceLogIds = [$attendanceLogId];
        }

        // Fetch visits if FieldSales module is active
        $visits = collect();
        $visitModel = $this->getModuleModel('FieldManager', \Modules\FieldManager\App\Models\Visit::class);
        if ($visitModel) {
            $visits = $visitModel::with('client:id,name')
                ->select('id', 'latitude', 'longitude', 'address', 'img_url', 'client_id', 'created_at')
                ->whereIn('attendance_log_id', $attendanceLogIds)
                ->limit(500)
                ->get()
                ->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'latitude' => $visit->latitude,
                        'longitude' => $visit->longitude,
                        'address' => $visit->address,
                        'img_url' => asset('storage/'.Constants::BaseFolderVisitImages.$visit->img_url),
                        'created_at' => $visit->created_at->format(Constants::TimeFormat),
                        'client_name' => $visit->client->name,
                    ];
                });
        }

        // Fetch breaks with location data from attendance log (only if BreakSystem module is active)
        $breaks = collect();
        $breakModel = $this->getModuleModel('BreakSystem', \Modules\BreakSystem\App\Models\AttendanceBreak::class);
        if ($breakModel) {
            $breaks = $breakModel::with('attendanceLog:id,latitude,longitude,address')
                ->select('id', 'attendance_log_id', 'start_time', 'end_time', 'created_at')
                ->whereIn('attendance_log_id', $attendanceLogIds)
                ->limit(200)
                ->get()
                ->map(function ($break) {
                    $attendanceLog = $break->attendanceLog;

                    return [
                        'id' => $break->id,
                        'latitude' => $attendanceLog?->latitude,
                        'longitude' => $attendanceLog?->longitude,
                        'address' => $attendanceLog?->address ?? 'N/A',
                        'start_time' => $break->start_time->format(Constants::TimeFormat),
                        'end_time' => $break->end_time ? $break->end_time->format(Constants::TimeFormat) : null,
                        'duration' => $break->end_time ? $break->start_time->diff($break->end_time)->format('%H:%I:%S') : null,
                        'created_at' => $break->created_at->format(Constants::TimeFormat),
                    ];
                });
        }

        // Fetch orders if ProductOrder module is active
        $orders = collect();
        $orderModel = $this->getModuleModel('ProductOrder', \Modules\ProductOrder\App\Models\ProductOrder::class);
        if ($orderModel) {
            $orders = $orderModel::withCount('orderLines')
                ->select('id', 'order_no', 'total', 'status', 'user_remarks', 'created_at')
                ->whereIn('attendance_log_id', $attendanceLogIds)
                ->limit(500)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_no,
                        'total_amount' => $order->total,
                        'status' => $order->status,
                        'total_items' => $order->order_lines_count,
                        'created_at' => $order->created_at->format(Constants::TimeFormat),
                        'user_remarks' => $order->user_remarks,
                    ];
                });
        }

        // Fetch tasks if FieldTask module is active
        $tasks = collect();
        $taskModel = $this->getModuleModel('FieldTask', \Modules\FieldTask\App\Models\Task::class);
        if ($taskModel) {
            $tasks = $taskModel::with('user:id,first_name,last_name')
                ->select('id', 'title', 'status', 'priority', 'latitude', 'longitude', 'user_id', 'for_date', 'due_date', 'created_at')
                ->where('user_id', $userId)
                ->whereDate('for_date', $date)
                ->limit(500)
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'latitude' => $task->latitude,
                        'longitude' => $task->longitude,
                        'for_date' => $task->for_date ? $task->for_date->format(Constants::TimeFormat) : null,
                        'due_date' => $task->due_date ? $task->due_date->format(Constants::TimeFormat) : null,
                        'created_at' => $task->created_at->format(Constants::TimeFormat),
                    ];
                });
        }

        return Success::response([
            'userId' => $userId,
            'date' => $date,
            'attendanceDuration' => $attendance->check_in_time && $attendance->check_out_time ?
              $attendance->check_in_time->diff($attendance->check_out_time)->format('%H:%I:%S') : 'N/A',
            'attendanceLogId' => $attendanceLogId,
            'name' => $attendance->user->getFullName(),
            'code' => $attendance->user->code,
            'designation' => $attendance->user->designation ? $attendance->user->designation->name : 'N/A',
            'visits' => $visits,
            'breaks' => $breaks,
            'orders' => $orders,
            'tasks' => $tasks,
            'visitsCount' => $visits->count(),
            'breaksCount' => $breaks->count(),
            'ordersCount' => $orders->count(),
            'tasksCount' => $tasks->count(),
            // Module availability status for frontend
            'modules' => [
                'fieldSales' => $visitModel !== null,
                'productOrder' => $orderModel !== null,
                'fieldTask' => $taskModel !== null,
            ],
        ]);
    }

    public function getDepartmentPerformanceAjax()
    {
        $departments = Department::where('status', Status::ACTIVE)
            ->with('designations')
            ->with('designations.users')
            ->get();

        $departmentPerformance = [];

        foreach ($departments as $department) {
            $departmentPerformance[] = [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'totalEmployees' => $department->designations->sum(function ($designation) {
                    return $designation->users->count();
                }),
                'totalPresentEmployees' => $department->designations->sum(function ($designation) {
                    return $designation->users->sum(function ($user) {
                        return Attendance::where('user_id', $user->id)->whereDate('created_at', now())->count();
                    });
                }),
                'totalAbsentEmployees' => $department->designations->sum(function ($designation) {
                    return $designation->users->count() - $designation->users->sum(function ($user) {
                        return Attendance::where('user_id', $user->id)->whereDate('created_at', now())->count();
                    });
                }),
            ];
        }

        return Success::response($departmentPerformance);
    }
}
