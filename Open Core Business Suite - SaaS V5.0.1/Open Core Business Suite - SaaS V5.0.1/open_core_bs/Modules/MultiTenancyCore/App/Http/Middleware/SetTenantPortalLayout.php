<?php

namespace Modules\MultiTenancyCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTenantPortalLayout
{
    /**
     * Handle an incoming request.
     *
     * Set horizontal layout for tenant portal pages
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('tenant')) {
                $pageConfigs = ['myLayout' => 'horizontal'];
                View::share('pageConfigs', $pageConfigs);
            }
        }

        return $next($request);
    }
}
