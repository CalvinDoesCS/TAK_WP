<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MultiTenancyCore\App\Models\SaasSetting;

class SaasSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Offline payment settings
        $offlineSettings = [
            'offline_payment_bank_name' => ['value' => '', 'type' => 'string', 'description' => 'Bank name for offline payments'],
            'offline_payment_account_name' => ['value' => '', 'type' => 'string', 'description' => 'Account holder name'],
            'offline_payment_account_number' => ['value' => '', 'type' => 'string', 'description' => 'Bank account number'],
            'offline_payment_routing_number' => ['value' => '', 'type' => 'string', 'description' => 'Bank routing number (optional)'],
            'offline_payment_swift_code' => ['value' => '', 'type' => 'string', 'description' => 'SWIFT/BIC code (optional)'],
            'offline_payment_bank_address' => ['value' => '', 'type' => 'string', 'description' => 'Bank address'],
            'offline_payment_payment_instructions' => ['value' => 'Please transfer the payment to our bank account and upload the receipt.', 'type' => 'string', 'description' => 'Instructions for customers'],
        ];

        foreach ($offlineSettings as $key => $data) {
            SaasSetting::set($key, $data['value'], $data['type'], $data['description']);
        }

        // Payment gateway enable/disable settings
        $gateways = ['offline', 'paypal', 'stripe', 'razorpay'];
        foreach ($gateways as $gateway) {
            SaasSetting::set(
                'payment_gateway_'.$gateway.'_enabled',
                $gateway === 'offline', // Only offline enabled by default
                'boolean',
                ucfirst($gateway).' payment gateway enabled status'
            );
        }

        // PayPal settings (if module exists)
        if (class_exists('Modules\\PayPalGateway\\Providers\\PayPalGatewayServiceProvider')) {
            $paypalSettings = [
                'payment_gateway_paypal_mode' => ['value' => 'sandbox', 'type' => 'string', 'description' => 'PayPal mode (sandbox/live)'],
                'payment_gateway_paypal_client_id' => ['value' => '', 'type' => 'string', 'description' => 'PayPal client ID'],
                'payment_gateway_paypal_client_secret' => ['value' => '', 'type' => 'string', 'description' => 'PayPal client secret'],
                'payment_gateway_paypal_webhook_id' => ['value' => '', 'type' => 'string', 'description' => 'PayPal webhook ID'],
                'payment_gateway_paypal_enable_recurring' => ['value' => false, 'type' => 'boolean', 'description' => 'Enable recurring payments'],
            ];

            foreach ($paypalSettings as $key => $data) {
                SaasSetting::set($key, $data['value'], $data['type'], $data['description']);
            }
        }

        // Stripe settings (if module exists)
        if (class_exists('Modules\\StripeGateway\\Providers\\StripeGatewayServiceProvider')) {
            $stripeSettings = [
                'payment_gateway_stripe_publishable_key' => ['value' => '', 'type' => 'string', 'description' => 'Stripe publishable key'],
                'payment_gateway_stripe_secret_key' => ['value' => '', 'type' => 'string', 'description' => 'Stripe secret key'],
                'payment_gateway_stripe_webhook_secret' => ['value' => '', 'type' => 'string', 'description' => 'Stripe webhook secret'],
            ];

            foreach ($stripeSettings as $key => $data) {
                SaasSetting::set($key, $data['value'], $data['type'], $data['description']);
            }
        }

        // Razorpay settings (if module exists)
        if (class_exists('Modules\\RazorpayGateway\\Providers\\RazorpayGatewayServiceProvider')) {
            $razorpaySettings = [
                'payment_gateway_razorpay_key_id' => ['value' => '', 'type' => 'string', 'description' => 'Razorpay key ID'],
                'payment_gateway_razorpay_key_secret' => ['value' => '', 'type' => 'string', 'description' => 'Razorpay key secret'],
                'payment_gateway_razorpay_webhook_secret' => ['value' => '', 'type' => 'string', 'description' => 'Razorpay webhook secret'],
            ];

            foreach ($razorpaySettings as $key => $data) {
                SaasSetting::set($key, $data['value'], $data['type'], $data['description']);
            }
        }

        // General SaaS Settings
        $generalSettings = [
            'general_allow_tenant_registration' => ['value' => true, 'type' => 'boolean', 'description' => 'Allow public tenant registration'],
            'general_auto_approve_tenants' => ['value' => false, 'type' => 'boolean', 'description' => 'Auto approve new tenant registrations'],
            'general_require_email_verification' => ['value' => true, 'type' => 'boolean', 'description' => 'Require email verification for tenants'],
            'general_default_plan_id' => ['value' => null, 'type' => 'integer', 'description' => 'Default subscription plan for new tenants'],
            'general_enable_trial' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable free trial period'],
            'general_trial_days' => ['value' => 14, 'type' => 'integer', 'description' => 'Number of days for free trial'],
            'general_require_payment_for_trial' => ['value' => false, 'type' => 'boolean', 'description' => 'Require payment method for trial'],
            'general_grace_period_days' => ['value' => 3, 'type' => 'integer', 'description' => 'Grace period after subscription expires'],
            'general_platform_name' => ['value' => 'ERP SaaS Platform', 'type' => 'string', 'description' => 'Platform name'],
            'general_support_email' => ['value' => 'support@example.com', 'type' => 'string', 'description' => 'Support email address'],
            'general_terms_url' => ['value' => '', 'type' => 'string', 'description' => 'Terms of service URL'],
            'general_privacy_url' => ['value' => '', 'type' => 'string', 'description' => 'Privacy policy URL'],
            'general_tenant_auto_provisioning' => ['value' => false, 'type' => 'boolean', 'description' => 'Enable automatic database provisioning (VPS mode)'],
            'general_tenant_db_host' => ['value' => 'localhost', 'type' => 'string', 'description' => 'Database host for tenant databases'],
            'general_tenant_db_port' => ['value' => '3306', 'type' => 'string', 'description' => 'Database port for tenant databases'],
            'general_currency' => ['value' => 'USD', 'type' => 'string', 'description' => 'Platform currency code'],
            'general_currency_symbol' => ['value' => '$', 'type' => 'string', 'description' => 'Platform currency symbol'],
            'general_enable_landing_page' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable landing page'],
        ];

        foreach ($generalSettings as $key => $data) {
            SaasSetting::set($key, $data['value'], $data['type'], $data['description']);
        }
    }
}
