<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Services\AddonService\IAddonService;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\SaasSetting;

class SaasSettingsController extends Controller
{
    protected $addonService;

    public function __construct(IAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Display SaaS settings page
     */
    public function index()
    {
        // Check if user is super admin or admin
        if (! auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('home')->with('error', __('Unauthorized access.'));
        }

        // Get current settings
        $offlineSettings = $this->getOfflinePaymentSettings();

        // Check which payment gateway addons are enabled
        $paymentGateways = [
            'PayPalGateway' => $this->addonService->isAddonEnabled('PayPalGateway'),
            'StripeGateway' => $this->addonService->isAddonEnabled('StripeGateway'),
            'RazorpayGateway' => $this->addonService->isAddonEnabled('RazorpayGateway'),
        ];

        // Get gateway enable/disable status
        $gatewayStatus = [
            'offline' => SaasSetting::get('payment_gateway_offline_enabled', false),
            'paypal' => SaasSetting::get('payment_gateway_paypal_enabled', false),
            'stripe' => SaasSetting::get('payment_gateway_stripe_enabled', false),
            'razorpay' => SaasSetting::get('payment_gateway_razorpay_enabled', false),
        ];

        // Get general settings
        $generalSettings = $this->getGeneralSettings();

        // Get plans for default plan selection
        $plans = \Modules\MultiTenancyCore\App\Models\Plan::where('is_active', true)->get();

        // Check if LandingPage module is enabled
        $landingPageEnabled = $this->addonService->isAddonEnabled('LandingPage');

        // Check if demo mode is enabled
        $isDemo = config('app.demo');

        return view('multitenancycore::admin.saas-settings.index', compact(
            'offlineSettings',
            'paymentGateways',
            'gatewayStatus',
            'generalSettings',
            'plans',
            'landingPageEnabled',
            'isDemo'
        ));
    }

    /**
     * Update offline payment settings
     */
    public function updateOfflinePayment(Request $request)
    {
        // Block updates in demo mode
        if (config('app.demo')) {
            return Error::response(__('Settings cannot be modified in demo mode.'));
        }

        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'routing_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'bank_address' => 'required|string',
            'payment_instructions' => 'required|string',
        ]);

        try {
            // Store settings in database or config
            $settings = [
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'routing_number' => $request->routing_number,
                'swift_code' => $request->swift_code,
                'bank_address' => $request->bank_address,
                'payment_instructions' => $request->payment_instructions,
            ];

            // Save to saas_settings table
            foreach ($settings as $key => $value) {
                SaasSetting::set('offline_payment_'.$key, $value, 'string');
            }

            return Success::response([
                'message' => 'Offline payment settings updated successfully.',
            ]);

        } catch (\Exception $e) {
            return Error::response('Failed to update settings: '.$e->getMessage());
        }
    }

    /**
     * Get offline payment settings
     */
    private function getOfflinePaymentSettings(): array
    {
        $keys = [
            'bank_name',
            'account_name',
            'account_number',
            'routing_number',
            'swift_code',
            'bank_address',
            'payment_instructions',
        ];

        $sensitiveKeys = ['account_number', 'routing_number', 'swift_code'];
        $isDemo = config('app.demo');

        $settings = [];
        foreach ($keys as $key) {
            $value = SaasSetting::get('offline_payment_'.$key, '');

            // Mask sensitive fields in demo mode
            if ($isDemo && in_array($key, $sensitiveKeys) && ! empty($value)) {
                $settings[$key] = '********';
            } else {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Toggle payment gateway status
     */
    public function toggleGateway(Request $request)
    {
        // Block updates in demo mode
        if (config('app.demo')) {
            return Error::response(__('Settings cannot be modified in demo mode.'));
        }

        // Convert checkbox value before validation
        $request->merge([
            'enabled' => filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN),
        ]);

        $request->validate([
            'gateway' => 'required|string|in:offline,paypal,stripe,razorpay',
            'enabled' => 'required|boolean',
        ]);

        try {
            SaasSetting::set(
                'payment_gateway_'.$request->gateway.'_enabled',
                $request->enabled,
                'boolean'
            );

            return Success::response([
                'message' => ucfirst($request->gateway).' gateway '.($request->enabled ? 'enabled' : 'disabled').' successfully.',
            ]);

        } catch (\Exception $e) {
            return Error::response('Failed to update gateway status: '.$e->getMessage());
        }
    }

    /**
     * Update general settings
     */
    public function updateGeneralSettings(Request $request)
    {
        // Block updates in demo mode
        if (config('app.demo')) {
            return Error::response(__('Settings cannot be modified in demo mode.'));
        }

        // Convert checkbox values before validation
        $request->merge([
            'allow_tenant_registration' => filter_var($request->allow_tenant_registration, FILTER_VALIDATE_BOOLEAN),
            'auto_approve_tenants' => filter_var($request->auto_approve_tenants, FILTER_VALIDATE_BOOLEAN),
            'require_email_verification' => filter_var($request->require_email_verification, FILTER_VALIDATE_BOOLEAN),
            'enable_trial' => filter_var($request->enable_trial, FILTER_VALIDATE_BOOLEAN),
            'require_payment_for_trial' => filter_var($request->require_payment_for_trial, FILTER_VALIDATE_BOOLEAN),
            'allow_plan_switching' => filter_var($request->allow_plan_switching, FILTER_VALIDATE_BOOLEAN),
            'prorate_plan_changes' => filter_var($request->prorate_plan_changes, FILTER_VALIDATE_BOOLEAN),
            'tenant_auto_provisioning' => filter_var($request->tenant_auto_provisioning, FILTER_VALIDATE_BOOLEAN),
        ]);

        // Clean up empty URL fields
        $urlFields = ['terms_url', 'privacy_url'];
        foreach ($urlFields as $field) {
            if ($request->has($field) && $request->$field === '') {
                $request->merge([$field => null]);
            }
        }

        $request->validate([
            'allow_tenant_registration' => 'required|boolean',
            'auto_approve_tenants' => 'required|boolean',
            'require_email_verification' => 'required|boolean',
            'default_plan_id' => 'nullable|exists:plans,id',
            'enable_trial' => 'required|boolean',
            'trial_days' => 'required|integer|min:1|max:365',
            'require_payment_for_trial' => 'required|boolean',
            'grace_period_days' => 'required|integer|min:0|max:30',
            'platform_name' => 'required|string|max:255',
            'support_email' => 'required|email|max:255',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'terms_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/.+|\/.*)/'],
            'privacy_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/.+|\/.*)/'],
            'tenant_auto_provisioning' => 'required|boolean',
            'tenant_db_host' => 'required|string|max:255',
            'tenant_db_port' => 'required|string|max:10',
            'tenant_db_username' => 'required|string|max:255',
            'tenant_db_password' => 'nullable|string|max:255',
        ]);

        try {
            $settings = [
                'allow_tenant_registration' => $request->allow_tenant_registration,
                'auto_approve_tenants' => $request->auto_approve_tenants,
                'require_email_verification' => $request->require_email_verification,
                'default_plan_id' => $request->default_plan_id,
                'enable_trial' => $request->enable_trial,
                'trial_days' => $request->trial_days,
                'require_payment_for_trial' => $request->require_payment_for_trial,
                'grace_period_days' => $request->grace_period_days,
                'platform_name' => $request->platform_name,
                'support_email' => $request->support_email,
                'currency' => $request->currency,
                'currency_symbol' => $request->currency_symbol,
                'terms_url' => $request->terms_url ?? '',
                'privacy_url' => $request->privacy_url ?? '',
                'tenant_auto_provisioning' => $request->tenant_auto_provisioning,
                'tenant_db_host' => $request->tenant_db_host,
                'tenant_db_port' => $request->tenant_db_port,
                'tenant_db_username' => $request->tenant_db_username,
                'tenant_db_password' => $request->tenant_db_password,
            ];

            $maskedValue = '********';
            $sensitiveKeys = ['tenant_db_password'];

            foreach ($settings as $key => $value) {
                // Skip updating sensitive fields if they contain the masked value
                if (in_array($key, $sensitiveKeys) && $value === $maskedValue) {
                    continue;
                }

                $type = 'string';
                if (in_array($key, ['allow_tenant_registration', 'auto_approve_tenants', 'require_email_verification',
                    'enable_trial', 'require_payment_for_trial', 'allow_plan_switching', 'prorate_plan_changes', 'tenant_auto_provisioning'])) {
                    $type = 'boolean';
                } elseif (in_array($key, ['trial_days', 'grace_period_days', 'default_plan_id'])) {
                    $type = 'integer';
                }

                SaasSetting::set('general_'.$key, $value, $type);
            }

            return Success::response([
                'message' => 'General settings updated successfully.',
            ]);

        } catch (\Exception $e) {
            return Error::response('Failed to update settings: '.$e->getMessage());
        }
    }

    /**
     * Get general settings
     */
    private function getGeneralSettings(): array
    {
        $keys = [
            'allow_tenant_registration' => ['default' => true, 'type' => 'boolean'],
            'auto_approve_tenants' => ['default' => false, 'type' => 'boolean'],
            'require_email_verification' => ['default' => true, 'type' => 'boolean'],
            'default_plan_id' => ['default' => null, 'type' => 'integer'],
            'enable_trial' => ['default' => true, 'type' => 'boolean'],
            'trial_days' => ['default' => 14, 'type' => 'integer'],
            'require_payment_for_trial' => ['default' => false, 'type' => 'boolean'],
            'grace_period_days' => ['default' => 3, 'type' => 'integer'],
            'platform_name' => ['default' => config('app.name'), 'type' => 'string'],
            'support_email' => ['default' => '', 'type' => 'string'],
            'currency' => ['default' => 'USD', 'type' => 'string'],
            'currency_symbol' => ['default' => '$', 'type' => 'string'],
            'terms_url' => ['default' => '', 'type' => 'string'],
            'privacy_url' => ['default' => '', 'type' => 'string'],
            'tenant_auto_provisioning' => ['default' => false, 'type' => 'boolean'],
            'tenant_db_host' => ['default' => 'localhost', 'type' => 'string'],
            'tenant_db_port' => ['default' => '3306', 'type' => 'string'],
            'tenant_db_username' => ['default' => 'root', 'type' => 'string'],
            'tenant_db_password' => ['default' => '', 'type' => 'string'],
        ];

        $sensitiveKeys = ['tenant_db_password'];
        $isDemo = config('app.demo');

        $settings = [];
        foreach ($keys as $key => $config) {
            $value = SaasSetting::get('general_'.$key, $config['default']);

            // Mask sensitive fields in demo mode
            if ($isDemo && in_array($key, $sensitiveKeys) && ! empty($value)) {
                $settings[$key] = '********';
            } else {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }
}
