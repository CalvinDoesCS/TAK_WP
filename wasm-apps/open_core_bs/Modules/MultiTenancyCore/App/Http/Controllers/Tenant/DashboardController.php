<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\Subscription;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (!$tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        // Check tenant status
        if ($tenant->status === 'rejected') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been rejected. Please contact support.');
        }

        if ($tenant->status === 'suspended') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
        }

        // Get subscription details
        $subscription = $tenant->activeSubscription;

        // Get user count (for now just 1, can be expanded later)
        $userCount = 1;

        // Get support tickets count (placeholder for future implementation)
        $supportTickets = 0;

        return view('multitenancycore::tenant.dashboard', compact(
            'tenant',
            'subscription',
            'userCount',
            'supportTickets'
        ));
    }

    /**
     * Display the pending approval page for tenants awaiting manual approval.
     */
    public function pendingApproval()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', __('Tenant record not found.'));
        }

        // If tenant is already approved, redirect to plan selection or dashboard
        if ($tenant->status === 'active') {
            // Check if tenant has an active subscription
            if ($tenant->activeSubscription) {
                return redirect()->route('multitenancycore.tenant.dashboard');
            }

            // No subscription yet, redirect to plan selection
            return redirect()->route('multitenancycore.tenant.plan-selection');
        }

        // Check if tenant was rejected
        if ($tenant->status === 'rejected') {
            auth()->logout();

            return redirect()->route('login')->with('error', __('Your account has been rejected. Please contact support.'));
        }

        return view('multitenancycore::tenant.pending-approval', compact('tenant'));
    }
}
