<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountStatus;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\AddonService\IAddonService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\GoogleReCAPTCHA\App\Rules\ReCaptchaRule;

class AuthController extends Controller
{
    protected $redirectTo = RouteServiceProvider::HOME;

    public function loginPost(Request $request)
    {
        try {

            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:8',
                'rememberMe' => 'nullable',
            ];

            // Add reCAPTCHA validation if module is enabled
            $addonService = app(IAddonService::class);
            if ($addonService->isAddonEnabled('GoogleReCAPTCHA')) {
                $rules['g-recaptcha-response'] = ['required', new ReCaptchaRule];
            }

            $request->validate($rules);

            $user = User::where('email', $request->email)->first();

            if (! empty($user)) {

                if ($user->status != UserAccountStatus::ACTIVE) {
                    return redirect()->back()->with('error', 'Your account is not active. Please contact the administrator.');
                }

                // Check if user's role has web access enabled
                // Tenant role users always have web access (they use the tenant portal)
                $role = $user->roles->first();
                $isTenantRole = $user->hasRole('tenant');
                if ($role && ! $role->is_web_access_enabled && ! $isTenantRole) {
                    return redirect()->back()->with('error', 'Web access is not enabled for your role. Please contact the administrator.');
                }

                $credentials = $request->only('email', 'password');
                $remember = $request->has('rememberMe') && $request->rememberMe === 'on';

                if (Auth::attempt($credentials, $remember)) {
                    if ($request->rememberMe) {
                        Auth::login($user, true);
                    } else {
                        Auth::login($user);
                    }

                    // Determine redirect based on user role and MultiTenancy status
                    $addonService = app(\App\Services\AddonService\IAddonService::class);
                    $isMultiTenancyEnabled = $addonService->isAddonEnabled('MultiTenancyCore');

                    // Check if user is logging in to central domain or tenant subdomain
                    $isTenantSubdomain = false;
                    if ($isMultiTenancyEnabled && app()->has('tenant')) {
                        $isTenantSubdomain = true;
                    }

                    // Redirect logic
                    if ($user->hasRole('tenant') && ! $isTenantSubdomain) {
                        // Tenant role on central domain → Tenant Portal
                        return redirect()->route('multitenancycore.tenant.dashboard')->with('success', __('Welcome back!'));
                    } elseif ($isTenantSubdomain) {
                        // Any user on tenant subdomain → Tenant's dashboard
                        return redirect()->route('dashboard')->with('success', __('Welcome back!'));
                    } else {
                        // Regular users on central domain → Main dashboard
                        return redirect()->route('dashboard')->with('success', __('Welcome back!'));
                    }

                } else {
                    return redirect()->back()->with('error', __('Invalid username or password.'));
                }
            } else {
                return redirect()->back()->with('error', __('User not found.'));
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return redirect()->back()->with('error', 'Oops! You have entered invalid credentials');
        }
    }

    /**
     * @deprecated Registration now handled by MultiTenancyCore module
     * @see \Modules\MultiTenancyCore\App\Http\Controllers\Tenant\RegistrationController
     */
    public function register()
    {
        // Redirect to MultiTenancyCore registration
        return redirect()->route('multitenancycore.register');
    }

    /**
     * @deprecated Registration now handled by MultiTenancyCore module
     * @see \Modules\MultiTenancyCore\App\Http\Controllers\Tenant\RegistrationController::register()
     */
    public function registerPost(Request $request)
    {
        // Redirect to MultiTenancyCore registration
        return redirect()->route('multitenancycore.register');
    }

    public function login()
    {

        if (auth()->user()) {
            return redirect('/');
        }

        /*   if (auth()->user()) {

             if (auth()->user()->hasRole('super_admin')) {
               return redirect()->route('superAdmin.dashboard')->with('success', 'Welcome back!');
             } else {

               if(tenancy()->initialized)
               {
                 return redirect()->route('customer.dashboard')->with('success', 'Welcome back!');
               }

               if (auth()->user()->email_verified_at == null) {
                 return redirect()->route('verification.notice')->with('error', 'Please verify your email address');
               }
               if(auth()->user()->hasRole('user')) {
                 return redirect()->route('customer.dashboard')->with('success', 'Welcome back!');
               }else{
                 return redirect()->route('dashboard')->with('success', 'Welcome back!');
               }
             }
           }*/

        $pageConfigs = ['myLayout' => 'blank'];

        return view('auth.login', ['pageConfigs' => $pageConfigs]);
    }

    public function logout()
    {
        if (Cache::has('accessible_module_routes')) {
            Cache::forget('accessible_module_routes');
        }
        auth()->logout();

        return redirect('auth/login')->with('success', 'Successfully logged out');
    }

    public function verifyEmail()
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect('/')->with('success', 'Email already verified');
        }
        $pageConfigs = ['myLayout' => 'blank'];

        return view('auth.verify-email', ['pageConfigs' => $pageConfigs]);
    }
}
