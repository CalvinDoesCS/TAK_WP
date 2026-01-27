<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;

class EnsureTenantRole
{
    /**
     * Routes that pending tenants can access
     */
    protected array $pendingAllowedRoutes = [
        'multitenancycore.tenant.pending-approval',
        'multitenancycore.tenant.usage',
        'multitenancycore.tenant.profile',
        'multitenancycore.tenant.profile.update',
        'multitenancycore.tenant.support',
        'multitenancycore.tenant.invoices',
        'multitenancycore.tenant.invoices.show',
        'multitenancycore.tenant.invoices.download',
        'auth.logout',
    ];

    /**
     * Routes that active tenants without subscription can access
     */
    protected array $planSelectionAllowedRoutes = [
        'multitenancycore.tenant.plan-selection',
        'multitenancycore.tenant.plan-selection.submit',
        'multitenancycore.tenant.plan-selection.payment-instructions',
        'multitenancycore.tenant.payment.instructions',
        'multitenancycore.tenant.payment.upload-proof',
        'multitenancycore.tenant.payment.details',
        'multitenancycore.tenant.payment.proof',
        'multitenancycore.tenant.billing',
        'multitenancycore.tenant.subscription',
        'multitenancycore.tenant.usage',
        'multitenancycore.tenant.profile',
        'multitenancycore.tenant.profile.update',
        'multitenancycore.tenant.support',
        'multitenancycore.tenant.invoices',
        'multitenancycore.tenant.invoices.show',
        'multitenancycore.tenant.invoices.download',
        'auth.logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user has tenant role
        if (! auth()->user()->hasRole('tenant')) {
            // If not a tenant, redirect to regular dashboard
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. This area is for tenants only.');
        }

        // Also check if there's a tenant record for this user
        $tenant = Tenant::where('email', auth()->user()->email)->first();
        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'Tenant record not found. Please contact support.');
        }

        // Get current route name for status-based redirects
        $currentRoute = $request->route()?->getName();

        // Handle tenant status-based routing
        $statusRedirect = $this->handleTenantStatus($tenant, $currentRoute);
        if ($statusRedirect) {
            return $statusRedirect;
        }

        // Share tenant data with all views
        view()->share('currentTenant', $tenant);

        return $next($request);
    }

    /**
     * Handle redirects based on tenant status
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function handleTenantStatus(Tenant $tenant, ?string $currentRoute)
    {
        // 1. Pending tenant -> show pending approval page
        if ($tenant->status === 'pending') {
            if (! in_array($currentRoute, $this->pendingAllowedRoutes)) {
                return redirect()->route('multitenancycore.tenant.pending-approval');
            }

            return null;
        }

        // 2. Rejected or cancelled tenant -> logout and show error
        if ($tenant->status === 'rejected' || $tenant->status === 'cancelled') {
            auth()->logout();

            return redirect()->route('auth.login')
                ->with('error', __('Your account has been rejected or cancelled. Please contact support.'));
        }

        // 3. Active tenant without subscription -> redirect to plan selection
        if ($tenant->status === 'active' && ! $tenant->hasActiveSubscription()) {
            if (! in_array($currentRoute, $this->planSelectionAllowedRoutes)) {
                return redirect()->route('multitenancycore.tenant.plan-selection');
            }

            return null;
        }

        // Tenant has valid status and subscription - allow access
        return null;
    }
}
