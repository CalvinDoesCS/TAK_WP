<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveReportService
{
    /**
     * Get accessible user IDs based on role.
     *
     * @param  int  $userId  Current user ID
     * @param  string  $role  User role (hr_admin, manager, executive, employee)
     * @return array User IDs that the user can access
     */
    public function getAccessibleUserIds(int $userId, string $role): array
    {
        // Normalize role name (case-insensitive comparison)
        $normalizedRole = strtolower($role);

        // Admin roles that can access all employees
        $adminRoles = ['admin', 'super admin', 'hr admin', 'hr manager', 'hr_admin'];

        if (in_array($normalizedRole, $adminRoles)) {
            // Admin can access all active employees
            return User::active()->pluck('id')->toArray();
        }

        switch ($normalizedRole) {
            case 'manager':
                // Manager can access direct and indirect reports
                $directReports = User::where('reporting_to_id', $userId)->pluck('id')->toArray();
                $indirectReports = $this->getIndirectReportIds($userId);

                return array_unique(array_merge($directReports, $indirectReports, [$userId]));

            case 'executive':
                // Executive gets aggregated data (return all for data collection, filtering happens in methods)
                return User::active()->pluck('id')->toArray();

            case 'employee':
            default:
                // Regular employee can only see their own data
                return [$userId];
        }
    }

    /**
     * Get indirect report IDs recursively (all subordinates in hierarchy).
     *
     * @param  int  $managerId  Manager user ID
     * @return array User IDs of all indirect reports
     */
    public function getIndirectReportIds(int $managerId): array
    {
        $indirectReports = [];
        $directReports = User::where('reporting_to_id', $managerId)->pluck('id')->toArray();

        foreach ($directReports as $reportId) {
            // Add the direct report
            $indirectReports[] = $reportId;

            // Recursively get their reports
            $subReports = $this->getIndirectReportIds($reportId);
            $indirectReports = array_merge($indirectReports, $subReports);
        }

        return array_unique($indirectReports);
    }

    /**
     * Get dashboard statistics.
     *
     * @param  array  $filters  Filter parameters (user_ids, date_from, date_to, year)
     * @return array Dashboard stats
     */
    public function getDashboardStats(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfYear();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfYear();

        // Total leaves taken in current year
        $totalLeavesTaken = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('leave_requests.status', LeaveRequestStatus::APPROVED->value)
            ->whereYear('from_date', $year)
            ->sum('total_days');

        // Pending approvals count
        $pendingApprovalsCount = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('leave_requests.status', LeaveRequestStatus::PENDING->value)
            ->count();

        // Available balance summary (average across all employees)
        $availableBalanceSummary = UserAvailableLeave::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('year', $year)
            ->selectRaw('
                SUM(entitled_leaves) as total_entitled,
                SUM(used_leaves) as total_used,
                SUM(available_leaves) as total_available
            ')
            ->first();

        // Employees on leave today
        $today = Carbon::today();
        $employeesOnLeaveToday = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('leave_requests.status', LeaveRequestStatus::APPROVED->value)
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->with('user:id,first_name,last_name,profile_picture')
            ->get()
            ->map(function ($leave) {
                return [
                    'user_id' => $leave->user_id,
                    'user_name' => $leave->user->getFullName(),
                    'leave_type' => $leave->leaveType->name ?? 'N/A',
                    'from_date' => $leave->from_date->format('Y-m-d'),
                    'to_date' => $leave->to_date->format('Y-m-d'),
                    'total_days' => $leave->total_days,
                ];
            })
            ->toArray();

        return [
            'total_leaves_taken' => (float) $totalLeavesTaken,
            'pending_approvals_count' => $pendingApprovalsCount,
            'available_balance' => [
                'total_entitled' => (float) ($availableBalanceSummary->total_entitled ?? 0),
                'total_used' => (float) ($availableBalanceSummary->total_used ?? 0),
                'total_available' => (float) ($availableBalanceSummary->total_available ?? 0),
            ],
            'employees_on_leave_today' => $employeesOnLeaveToday,
            'employees_on_leave_count' => count($employeesOnLeaveToday),
        ];
    }

    /**
     * Get monthly trend data for charts.
     *
     * @param  int  $year  Year to analyze
     * @param  array  $filters  Filter parameters (user_ids)
     * @return array Monthly trend data
     */
    public function getMonthlyTrendData(int $year, array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;

        $monthlyData = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->whereYear('from_date', $year)
            ->where('leave_requests.status', LeaveRequestStatus::APPROVED->value)
            ->selectRaw('MONTH(from_date) as month, COUNT(*) as count, SUM(total_days) as total_days')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill in missing months with zero
        $months = [];
        $counts = [];
        $totalDays = [];

        for ($month = 1; $month <= 12; $month++) {
            $months[] = Carbon::create($year, $month, 1)->format('M');
            $counts[] = $monthlyData->get($month)->count ?? 0;
            $totalDays[] = (float) ($monthlyData->get($month)->total_days ?? 0);
        }

        return [
            'months' => $months,
            'counts' => $counts,
            'total_days' => $totalDays,
        ];
    }

    /**
     * Get leave type distribution data.
     *
     * @param  array  $filters  Filter parameters (user_ids, year)
     * @return array Leave type distribution
     */
    public function getLeaveTypeDistribution(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');

        $distribution = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('leave_requests.status', LeaveRequestStatus::APPROVED->value)
            ->whereYear('from_date', $year)
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->selectRaw('leave_types.name, leave_types.code, COUNT(*) as count, SUM(leave_requests.total_days) as total_days')
            ->groupBy('leave_types.id', 'leave_types.name', 'leave_types.code')
            ->orderByDesc('total_days')
            ->get();

        return [
            'labels' => $distribution->pluck('name')->toArray(),
            'codes' => $distribution->pluck('code')->toArray(),
            'counts' => $distribution->pluck('count')->toArray(),
            'total_days' => $distribution->pluck('total_days')->map(fn ($val) => (float) $val)->toArray(),
        ];
    }

    /**
     * Get department utilization data.
     *
     * @param  array  $filters  Filter parameters (user_ids, year)
     * @return array Department utilization
     */
    public function getDepartmentUtilization(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');

        $departmentData = UserAvailableLeave::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('year', $year)
            ->join('users', 'users_available_leaves.user_id', '=', 'users.id')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->selectRaw('
                teams.id,
                teams.name,
                SUM(users_available_leaves.entitled_leaves) as total_entitled,
                SUM(users_available_leaves.used_leaves) as total_used,
                COUNT(DISTINCT users.id) as employee_count
            ')
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('total_used')
            ->get();

        return [
            'labels' => $departmentData->pluck('name')->toArray(),
            'entitled' => $departmentData->pluck('total_entitled')->map(fn ($val) => (float) $val)->toArray(),
            'used' => $departmentData->pluck('total_used')->map(fn ($val) => (float) $val)->toArray(),
            'utilization_percentage' => $departmentData->map(function ($dept) {
                return $this->calculateUtilizationRate($dept->total_used, $dept->total_entitled);
            })->toArray(),
            'employee_count' => $departmentData->pluck('employee_count')->toArray(),
        ];
    }

    /**
     * Get status distribution data.
     *
     * @param  array  $filters  Filter parameters (user_ids, year)
     * @return array Status distribution
     */
    public function getStatusDistribution(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');

        $statusData = LeaveRequest::query()
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->whereYear('from_date', $year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                // Convert enum to string value for keyBy
                $statusValue = $item->status instanceof \BackedEnum ? $item->status->value : $item->status;

                return [$statusValue => $item];
            });

        $statuses = [
            LeaveRequestStatus::APPROVED->value => __('Approved'),
            LeaveRequestStatus::PENDING->value => __('Pending'),
            LeaveRequestStatus::REJECTED->value => __('Rejected'),
            LeaveRequestStatus::CANCELLED->value => __('Cancelled'),
        ];

        $labels = [];
        $counts = [];

        foreach ($statuses as $statusValue => $statusLabel) {
            $labels[] = $statusLabel;
            $counts[] = $statusData->get($statusValue)->count ?? 0;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    /**
     * Get leave balances report data.
     *
     * @param  array  $filters  Filter parameters (user_ids, leave_type_id, expiring_soon)
     * @return array Leave balances
     */
    public function getLeaveBalances(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $leaveTypeId = $filters['leave_type_id'] ?? null;
        $expiringSoon = $filters['expiring_soon'] ?? false;
        $year = $filters['year'] ?? date('Y');

        $query = UserAvailableLeave::query()
            ->with(['user.team', 'leaveType'])
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->where('year', $year);

        if ($expiringSoon) {
            $query->whereNotNull('carry_forward_expiry_date')
                ->where('carry_forward_expiry_date', '<=', Carbon::now()->addDays(30));
        }

        return $query->get()->map(function ($balance) {
            return [
                'user_id' => $balance->user_id,
                'user_name' => $balance->user->getFullName(),
                'employee_code' => $balance->user->code,
                'department' => $balance->user->team->name ?? 'N/A',
                'leave_type_name' => $balance->leaveType->name,
                'leave_type_code' => $balance->leaveType->code,
                'entitled' => (float) $balance->entitled_leaves,
                'carried_forward' => (float) $balance->carried_forward_leaves,
                'additional' => (float) $balance->additional_leaves,
                'used' => (float) $balance->used_leaves,
                'available' => (float) $balance->available_leaves,
                'carry_forward_expiry_date' => $balance->carry_forward_expiry_date?->format('Y-m-d'),
                'is_expiring_soon' => $this->isExpiringSoon($balance->carry_forward_expiry_date),
                'utilization_rate' => $this->calculateUtilizationRate($balance->used_leaves, $balance->entitled_leaves),
            ];
        })->toArray();
    }

    /**
     * Get leave history report data.
     *
     * @param  array  $filters  Filter parameters (user_ids, leave_type_id, status, date_from, date_to)
     * @return array Leave history
     */
    public function getLeaveHistory(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $leaveTypeId = $filters['leave_type_id'] ?? null;
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query = LeaveRequest::query()
            ->with(['user.team', 'leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
            ->when($status, fn ($q) => $q->where('leave_requests.status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('from_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('to_date', '<=', $dateTo))
            ->orderByDesc('created_at');

        return $query->get()->map(function ($leave) {
            return [
                'id' => $leave->id,
                'user_id' => $leave->user_id,
                'user_name' => $leave->user->getFullName(),
                'employee_code' => $leave->user->code,
                'department' => $leave->user->team->name ?? 'N/A',
                'leave_type_name' => $leave->leaveType->name,
                'from_date' => $leave->from_date->format('Y-m-d'),
                'to_date' => $leave->to_date->format('Y-m-d'),
                'total_days' => (float) $leave->total_days,
                'is_half_day' => $leave->is_half_day,
                'half_day_type' => $leave->half_day_type,
                'status' => $leave->status->value,
                'status_label' => $leave->status->name,
                'approved_by' => $leave->approvedBy?->getFullName(),
                'approved_at' => $leave->approved_at?->format('Y-m-d H:i:s'),
                'rejected_by' => $leave->rejectedBy?->getFullName(),
                'rejected_at' => $leave->rejected_at?->format('Y-m-d H:i:s'),
                'cancelled_by' => $leave->cancelledBy?->getFullName(),
                'cancelled_at' => $leave->cancelled_at?->format('Y-m-d H:i:s'),
                'approval_notes' => $leave->approval_notes,
                'user_notes' => $leave->user_notes,
            ];
        })->toArray();
    }

    /**
     * Get department statistics.
     *
     * @param  array  $filters  Filter parameters (user_ids, year)
     * @return array Department statistics
     */
    public function getDepartmentStatistics(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');

        $departmentStats = DB::table('users_available_leaves')
            ->join('users', 'users_available_leaves.user_id', '=', 'users.id')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->when($userIds, fn ($q) => $q->whereIn('users.id', $userIds))
            ->where('users_available_leaves.year', $year)
            ->selectRaw('
                teams.id as department_id,
                teams.name as department_name,
                COUNT(DISTINCT users.id) as employee_count,
                SUM(users_available_leaves.entitled_leaves) as total_entitled,
                SUM(users_available_leaves.used_leaves) as total_used,
                SUM(users_available_leaves.available_leaves) as total_available,
                AVG(users_available_leaves.used_leaves) as avg_per_employee
            ')
            ->groupBy('teams.id', 'teams.name')
            ->orderBy('teams.name')
            ->get();

        return $departmentStats->map(function ($dept) {
            return [
                'department_id' => $dept->department_id,
                'department_name' => $dept->department_name,
                'employee_count' => $dept->employee_count,
                'total_entitled' => (float) $dept->total_entitled,
                'total_used' => (float) $dept->total_used,
                'total_available' => (float) $dept->total_available,
                'avg_per_employee' => (float) $dept->avg_per_employee,
                'utilization_rate' => $this->calculateUtilizationRate($dept->total_used, $dept->total_entitled),
            ];
        })->toArray();
    }

    /**
     * Get compliance data for monitoring.
     *
     * @param  array  $filters  Filter parameters (user_ids)
     * @return array Compliance data
     */
    public function getComplianceData(array $filters = []): array
    {
        $userIds = $filters['user_ids'] ?? null;
        $year = $filters['year'] ?? date('Y');

        // Expiring carry forward leaves (within 30 days)
        $expiringLeaves = UserAvailableLeave::query()
            ->with(['user.team', 'leaveType'])
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('year', $year)
            ->whereNotNull('carry_forward_expiry_date')
            ->where('carry_forward_expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('carry_forward_expiry_date', '>=', Carbon::now())
            ->where('carried_forward_leaves', '>', 0)
            ->get()
            ->map(function ($balance) {
                return [
                    'user_id' => $balance->user_id,
                    'user_name' => $balance->user->getFullName(),
                    'employee_code' => $balance->user->code,
                    'department' => $balance->user->team->name ?? 'N/A',
                    'leave_type_name' => $balance->leaveType->name,
                    'carried_forward_leaves' => (float) $balance->carried_forward_leaves,
                    'expiry_date' => $balance->carry_forward_expiry_date->format('Y-m-d'),
                    'days_until_expiry' => Carbon::now()->diffInDays($balance->carry_forward_expiry_date, false),
                ];
            })
            ->toArray();

        // Encashment eligible employees (leave types that allow encashment)
        $encashmentEligible = UserAvailableLeave::query()
            ->with(['user.team', 'leaveType'])
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('year', $year)
            ->whereHas('leaveType', function ($q) {
                $q->where('allow_encashment', true);
            })
            ->where('available_leaves', '>', 0)
            ->get()
            ->map(function ($balance) {
                $maxEncashment = min($balance->available_leaves, $balance->leaveType->max_encashment_days ?? 0);

                return [
                    'user_id' => $balance->user_id,
                    'user_name' => $balance->user->getFullName(),
                    'employee_code' => $balance->user->code,
                    'department' => $balance->user->team->name ?? 'N/A',
                    'leave_type_name' => $balance->leaveType->name,
                    'available_leaves' => (float) $balance->available_leaves,
                    'max_encashment_days' => (float) $maxEncashment,
                    'is_eligible' => $maxEncashment > 0,
                ];
            })
            ->filter(fn ($item) => $item['is_eligible'])
            ->values()
            ->toArray();

        // High unused balance alerts (> 80% entitled leaves unused)
        $highUnusedBalance = UserAvailableLeave::query()
            ->with(['user.team', 'leaveType'])
            ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
            ->where('year', $year)
            ->where('entitled_leaves', '>', 0)
            ->get()
            ->filter(function ($balance) {
                $utilizationRate = $this->calculateUtilizationRate($balance->used_leaves, $balance->entitled_leaves);

                return $utilizationRate < 20; // Less than 20% utilized = more than 80% unused
            })
            ->map(function ($balance) {
                return [
                    'user_id' => $balance->user_id,
                    'user_name' => $balance->user->getFullName(),
                    'employee_code' => $balance->user->code,
                    'department' => $balance->user->team->name ?? 'N/A',
                    'leave_type_name' => $balance->leaveType->name,
                    'entitled_leaves' => (float) $balance->entitled_leaves,
                    'used_leaves' => (float) $balance->used_leaves,
                    'available_leaves' => (float) $balance->available_leaves,
                    'utilization_rate' => $this->calculateUtilizationRate($balance->used_leaves, $balance->entitled_leaves),
                ];
            })
            ->values()
            ->toArray();

        return [
            'expiring_leaves' => $expiringLeaves,
            'expiring_leaves_count' => count($expiringLeaves),
            'encashment_eligible' => $encashmentEligible,
            'encashment_eligible_count' => count($encashmentEligible),
            'high_unused_balance' => $highUnusedBalance,
            'high_unused_balance_count' => count($highUnusedBalance),
        ];
    }

    /**
     * Calculate utilization rate percentage.
     *
     * @param  float  $used  Used leaves
     * @param  float  $entitled  Entitled leaves
     * @return float Utilization rate percentage
     */
    public function calculateUtilizationRate(float $used, float $entitled): float
    {
        if ($entitled <= 0) {
            return 0.0;
        }

        return round(($used / $entitled) * 100, 2);
    }

    /**
     * Check if a date is expiring soon (within N days).
     *
     * @param  \Carbon\Carbon|null  $expiryDate  Expiry date
     * @param  int  $days  Number of days threshold (default 30)
     * @return bool True if expiring soon
     */
    public function isExpiringSoon($expiryDate, int $days = 30): bool
    {
        if (! $expiryDate instanceof Carbon) {
            return false;
        }

        $now = Carbon::now();
        $threshold = $now->copy()->addDays($days);

        return $expiryDate->between($now, $threshold);
    }
}
