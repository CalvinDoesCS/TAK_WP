<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->hasRole('tenant')) {
            $tenant = Tenant::where('email', auth()->user()->email)->first();
            
            if (!$tenant) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Tenant record not found. Please contact support.');
            }
            
            // Check tenant status
            if ($tenant->status === 'suspended') {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
            }
            
            if ($tenant->status === 'cancelled') {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Your account has been cancelled. Please contact support to reactivate.');
            }
            
            // Share tenant data with all views
            view()->share('currentTenant', $tenant);
        }
        
        return $next($request);
    }
}