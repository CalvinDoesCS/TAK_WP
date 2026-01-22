<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;

class DashboardController extends Controller
{
    /**
     * Display multitenancy dashboard
     */
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_subscriptions' => Subscription::whereIn('status', ['trial', 'active'])
                ->whereHas('tenant', function ($query) {
                    $query->where('status', 'active');
                })
                ->count(),
            'pending_approvals' => Tenant::where('status', 'pending')->count(),
            'monthly_revenue' => $this->calculateMonthlyRevenue(),
        ];

        $recentTenants = Tenant::with('activeSubscription.plan')
            ->latest()
            ->limit(5)
            ->get();

        $expiringSubscriptions = Subscription::with(['tenant', 'plan'])
            ->expiringSoon()
            ->limit(5)
            ->get();

        $planDistribution = $this->getPlanDistribution();

        return view('multitenancycore::admin.dashboard', compact(
            'stats',
            'recentTenants',
            'expiringSubscriptions',
            'planDistribution'
        ));
    }

    /**
     * Calculate monthly revenue
     */
    private function calculateMonthlyRevenue()
    {
        return Payment::successful()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Get plan distribution data
     */
    private function getPlanDistribution()
    {
        $plans = Plan::withCount(['subscriptions' => function ($query) {
            $query->whereIn('status', ['trial', 'active'])
                ->whereHas('tenant', function ($q) {
                    $q->where('status', 'active');
                });
        }])->get();

        return $plans->map(function ($plan) {
            return [
                'name' => $plan->name,
                'count' => $plan->subscriptions_count,
            ];
        });
    }

    /**
     * Get dashboard statistics via AJAX
     */
    public function statistics()
    {
        $monthlyRevenue = [];

        // Get revenue for last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = Payment::successful()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');

            $monthlyRevenue[] = [
                'month' => $date->format('M'),
                'revenue' => $revenue,
            ];
        }

        $response = [
            'stats' => [
                'total_tenants' => Tenant::count(),
                'active_subscriptions' => Subscription::whereIn('status', ['trial', 'active'])
                    ->whereHas('tenant', function ($query) {
                        $query->where('status', 'active');
                    })
                    ->count(),
                'pending_approvals' => Tenant::where('status', 'pending')->count(),
                'monthly_revenue' => $this->calculateMonthlyRevenue(),
                'pending_payments' => Payment::pending()->count(),
            ],
            'monthly_revenue' => $monthlyRevenue,
            'plan_distribution' => $this->getPlanDistribution(),
        ];

        return response()->json($response);
    }
}
