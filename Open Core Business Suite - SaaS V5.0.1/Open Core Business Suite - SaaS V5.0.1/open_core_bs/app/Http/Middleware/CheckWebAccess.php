<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckWebAccess
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user's role has web access enabled.
     * If not, log them out and redirect to login with an error message.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $role = $user->roles->first();

            // If user has a role and web access is disabled, log them out
            if ($role && ! $role->is_web_access_enabled) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', __('Web access is not enabled for your role. Please contact the administrator.'));
            }
        }

        return $next($request);
    }
}
