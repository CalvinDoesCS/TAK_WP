<?php

namespace App\Http\Controllers\Employee;

use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AddonService\IAddonService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\GoogleReCAPTCHA\App\Rules\ReCaptchaRule;

class EmployeeAuthController extends Controller
{
    public function login()
    {
        if (auth()->user()) {
            $user = auth()->user();
            $role = $user->roles->first();

            // Redirect employees to employee dashboard
            $hasEmployeeRole = $user->hasRole('employee') ||
                               $user->hasRole('field_employee') ||
                               $user->hasRole('office_employee') ||
                               $user->hasRole('manager');

            if ($hasEmployeeRole) {
                return redirect()->route('employee.dashboard');
            }

            // Redirect non-employees to appropriate dashboard
            return redirect('/');
        }

        $pageConfigs = ['myLayout' => 'blank'];

        return view('employee.auth.login', ['pageConfigs' => $pageConfigs]);
    }

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
                // Check if user is active
                if ($user->status != UserAccountStatus::ACTIVE) {
                    return redirect()->back()->with('error', __('Your account is not active. Please contact the administrator.'));
                }

                // Check if user has any employee-type role
                $hasEmployeeRole = $user->hasRole('employee') ||
                                   $user->hasRole('field_employee') ||
                                   $user->hasRole('office_employee') ||
                                   $user->hasRole('manager');

                if (! $hasEmployeeRole) {
                    return redirect()->back()->with('error', __('Access denied. This portal is for employees only.'));
                }

                // Check if user's role has web access enabled
                $role = $user->roles->first();
                if ($role && ! $role->is_web_access_enabled) {
                    return redirect()->back()->with('error', __('Web access is not enabled for your role. Please contact the administrator.'));
                }

                $credentials = $request->only('email', 'password');
                $remember = $request->has('rememberMe') && $request->rememberMe === 'on';

                if (Auth::attempt($credentials, $remember)) {
                    if ($request->rememberMe) {
                        Auth::login($user, true);
                    } else {
                        Auth::login($user);
                    }

                    return redirect()->route('employee.dashboard')->with('success', __('Welcome back!'));
                } else {
                    return redirect()->back()->with('error', __('Invalid username or password.'));
                }
            } else {
                return redirect()->back()->with('error', __('User not found.'));
            }
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return redirect()->back()->with('error', __('Oops! You have entered invalid credentials'));
        }
    }

    public function logout()
    {
        auth()->logout();

        return redirect()->route('employee.login')->with('success', __('Successfully logged out'));
    }
}
