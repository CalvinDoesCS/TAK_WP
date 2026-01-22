<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AddonService\IAddonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\MultiTenancyCore\App\Http\Requests\TenantRegistrationRequest;
use Modules\MultiTenancyCore\App\Http\Requests\ValidateRegistrationFieldRequest;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\PlanSelectionService;

class RegistrationController extends Controller
{
    protected $addonService;

    public function __construct(IAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Show the tenant registration form.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        // Check if tenant registration is allowed
        if (! SaasSetting::get('general_allow_tenant_registration', true)) {
            return redirect()->route('login')->with('error', 'Tenant registration is currently disabled.');
        }

        // Check if user is already logged in
        if (auth()->check()) {
            // If user is already a tenant, redirect to their dashboard
            if (auth()->user()->hasRole('tenant')) {
                return redirect()->route('multitenancycore.tenant.dashboard');
            }

            // If user has other roles, they shouldn't register as tenant
            auth()->logout();

            return redirect()->route('multitenancycore.register')
                ->with('info', 'Please register with a new account to become a tenant.');
        }

        // Check if MultiTenancyCore is enabled
        if (! $this->addonService->isAddonEnabled('MultiTenancyCore')) {
            return redirect()->route('login')
                ->with('error', 'Tenant registration is not available at this time.');
        }

        // Get active plans for selection (optional - registration works without plans)
        $plans = Plan::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        // Get settings for the view
        $defaultPlanId = SaasSetting::get('general_default_plan_id');
        $trialEnabled = SaasSetting::get('general_enable_trial', true);
        $trialDays = SaasSetting::get('general_trial_days', 14);
        $requirePaymentForTrial = SaasSetting::get('general_require_payment_for_trial', false);
        $termsUrl = SaasSetting::get('general_terms_url', '');
        $privacyUrl = SaasSetting::get('general_privacy_url', '');

        $pageConfigs = ['myLayout' => 'blank'];

        return view('multitenancycore::tenant.register', compact(
            'plans',
            'pageConfigs',
            'defaultPlanId',
            'trialEnabled',
            'trialDays',
            'requirePaymentForTrial',
            'termsUrl',
            'privacyUrl'
        ));
    }

    /**
     * Validate a single field for real-time validation.
     */
    public function validateField(ValidateRegistrationFieldRequest $request): JsonResponse
    {
        // If we reach here, validation passed (Form Request handles validation errors)
        return response()->json([
            'success' => true,
            'field' => $request->input('field'),
            'message' => __('Field is valid'),
        ]);
    }

    /**
     * Handle tenant registration.
     */
    public function register(TenantRegistrationRequest $request): JsonResponse|RedirectResponse
    {
        // Validation is handled by TenantRegistrationRequest

        // Handle email verification based on settings
        $requireEmailVerification = SaasSetting::get('general_require_email_verification', true);

        // Check auto-approval setting
        $autoApprove = SaasSetting::get('general_auto_approve_tenants', false);

        try {
            DB::beginTransaction();

            // Generate unique tenant code
            $lastTenant = User::withTrashed()
                ->where('code', 'LIKE', 'TENANT-%')
                ->orderBy('code', 'desc')
                ->first();

            if ($lastTenant && preg_match('/TENANT-(\d+)/', $lastTenant->code, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            } else {
                // Start from 001 for first tenant
                $nextNumber = 1;
            }

            $tenantCode = 'TENANT-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Create user account
            $user = new User;
            $user->first_name = $request->firstName;
            $user->last_name = $request->lastName;
            $user->gender = $request->gender;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);

            $user->email_verified_at = (! $requireEmailVerification || config('app.demo')) ? now() : null;

            $user->code = $tenantCode;
            $user->save();

            // Assign tenant role
            $user->assignRole('tenant');

            // Create tenant record
            $tenant = new Tenant;
            $tenant->name = $request->company_name;
            $tenant->email = $request->email;
            $tenant->phone = $request->phone;
            $tenant->subdomain = $request->subdomain;

            $tenant->status = $autoApprove ? 'active' : 'pending';

            // If auto-approved, set approved details
            if ($autoApprove) {
                $tenant->approved_at = now();
                $tenant->approved_by_id = 1; // System approval
            }

            $tenant->save();

            // Store registration metadata for admin approval
            $tenant->update([
                'metadata' => [
                    'registration_date' => now()->toDateTimeString(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tenant registration failed: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Registration failed. Please try again.'),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', __('Registration failed. Please try again.'));
        }

        // Auto-provision database OUTSIDE transaction (DDL statements cause implicit commits)
        if ($autoApprove && SaasSetting::get('general_tenant_auto_provisioning', false)) {
            try {
                $databaseService = app(\Modules\MultiTenancyCore\App\Services\TenantDatabaseService::class);

                // Create database
                $result = $databaseService->createDatabase($tenant);

                if ($result['success']) {
                    // Run migrations and seeders
                    $databaseService->migrateAndSeed($tenant);

                    // Update provisioning status
                    $tenant->update([
                        'database_provisioning_status' => 'provisioned',
                    ]);

                    Log::info('Auto-provisioned database for tenant: '.$tenant->id);
                } else {
                    Log::error('Failed to auto-provision database for tenant: '.$tenant->id.' - '.$result['message']);
                    // Don't fail the registration, just log the error
                }
            } catch (\Exception $e) {
                Log::error('Auto-provisioning error for tenant '.$tenant->id.': '.$e->getMessage());
                // Don't fail the registration, just log the error
            }
        }

        // Auto-assign default plan OUTSIDE transaction (like auto-provisioning)
        $planAssignmentResult = null;
        if ($autoApprove) {
            $defaultPlanId = SaasSetting::get('general_default_plan_id');
            if ($defaultPlanId) {
                $plan = Plan::active()->find($defaultPlanId);
                if ($plan) {
                    $planService = app(PlanSelectionService::class);

                    // For paid plans without trial, check if payment methods exist
                    $trialEligible = $planService->isTrialEligible($tenant);
                    $trialEnabled = SaasSetting::get('general_enable_trial', true);
                    $canUseTrial = $trialEligible && $trialEnabled && ! $plan->isFree();
                    $requirePaymentForTrial = SaasSetting::get('general_require_payment_for_trial', false);

                    if ($plan->isFree()) {
                        // Free plan: assign immediately
                        $planAssignmentResult = $planService->selectPlan($tenant, $plan, null);
                    } elseif ($canUseTrial) {
                        if ($requirePaymentForTrial) {
                            // Trial requires payment method - get one
                            $paymentMethods = $planService->getAvailablePaymentMethods();
                            if (! empty($paymentMethods)) {
                                $paymentMethod = in_array('bank_transfer', $paymentMethods)
                                    ? 'bank_transfer'
                                    : $paymentMethods[0];
                                $planAssignmentResult = $planService->selectPlan($tenant, $plan, $paymentMethod);
                            } else {
                                Log::warning('Trial requires payment but no payment methods available', [
                                    'tenant_id' => $tenant->id,
                                    'plan_id' => $plan->id,
                                ]);
                            }
                        } else {
                            // Trial without payment method required
                            $planAssignmentResult = $planService->selectPlan($tenant, $plan, null);
                        }
                    } else {
                        // Paid plan, no trial: check payment methods
                        $paymentMethods = $planService->getAvailablePaymentMethods();
                        if (! empty($paymentMethods)) {
                            // Use first available payment method (bank_transfer preferred)
                            $paymentMethod = in_array('bank_transfer', $paymentMethods)
                                ? 'bank_transfer'
                                : $paymentMethods[0];
                            $planAssignmentResult = $planService->selectPlan($tenant, $plan, $paymentMethod);
                        } else {
                            Log::warning('Default plan is paid but no payment methods available', [
                                'tenant_id' => $tenant->id,
                                'plan_id' => $plan->id,
                            ]);
                        }
                    }
                } else {
                    Log::warning('Default plan not found or inactive', ['plan_id' => $defaultPlanId]);
                }
            }
        }

        // Send verification email if required and not demo
        if ($requireEmailVerification && ! config('app.demo') && ! $user->email_verified_at) {
            $user->sendEmailVerificationNotification();
        }

        // Log the user in
        auth()->login($user);

        // Redirect based on status and email verification
        $platformName = SaasSetting::get('general_platform_name', config('app.name'));

        if ($requireEmailVerification && ! $user->email_verified_at) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Registration successful! Please verify your email to continue.'),
                    'redirect' => route('verification.notice'),
                    'requiresVerification' => true,
                ]);
            }

            return redirect()->route('verification.notice')
                ->with('success', __('Registration successful! Please verify your email to continue.'));
        }

        // Check if we need to redirect to payment instead of dashboard
        if ($planAssignmentResult && isset($planAssignmentResult['payment']) && $planAssignmentResult['payment']) {
            $payment = $planAssignmentResult['payment'];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Registration successful! Please complete your payment.'),
                    'redirect' => route('multitenancycore.tenant.payment.instructions', $payment->id),
                    'requiresPayment' => true,
                ]);
            }

            return redirect()->route('multitenancycore.tenant.payment.instructions', $payment->id)
                ->with('success', __('Registration successful! Please complete your payment to activate.'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Registration successful! Welcome to :platform.', ['platform' => $platformName]),
                'redirect' => route('multitenancycore.tenant.dashboard'),
                'requiresVerification' => false,
            ]);
        }

        return redirect()->route('multitenancycore.tenant.dashboard')
            ->with('success', __('Registration successful! Welcome to :platform.', ['platform' => $platformName]));
    }
}
