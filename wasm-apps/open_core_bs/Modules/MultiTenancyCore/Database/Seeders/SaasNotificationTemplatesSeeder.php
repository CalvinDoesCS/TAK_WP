<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MultiTenancyCore\App\Models\SaasNotificationTemplate;

class SaasNotificationTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Tenant Registration
            [
                'key' => 'tenant_welcome',
                'name' => 'Welcome Email',
                'category' => 'tenant',
                'subject' => 'Welcome to {app_name}!',
                'body' => "Hi {tenant_name},\n\nWelcome to {app_name}! Your account has been created successfully.\n\nCompany: {company_name}\nEmail: {email}\nSubdomain: {subdomain}\n\nYou can access your account at: {login_url}\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'company_name', 'email', 'subdomain', 'login_url'],
            ],

            // Trial Expiry
            [
                'key' => 'trial_expiring',
                'name' => 'Trial Expiring Soon',
                'category' => 'subscription',
                'subject' => 'Your trial expires in {days} days',
                'body' => "Hi {tenant_name},\n\nYour trial period for {app_name} will expire in {days} days on {expiry_date}.\n\nTo continue using our service, please upgrade your subscription.\n\nUpgrade now: {upgrade_url}\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'days', 'expiry_date', 'upgrade_url'],
            ],

            // Subscription Created
            [
                'key' => 'subscription_created',
                'name' => 'Subscription Created',
                'category' => 'subscription',
                'subject' => 'Subscription Activated - {plan_name}',
                'body' => "Hi {tenant_name},\n\nYour subscription has been activated successfully!\n\nPlan: {plan_name}\nBilling Cycle: {billing_cycle}\nAmount: {amount}\nNext Billing Date: {next_billing_date}\n\nThank you for choosing {app_name}!\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'plan_name', 'billing_cycle', 'amount', 'next_billing_date'],
            ],

            // Payment Received
            [
                'key' => 'payment_received',
                'name' => 'Payment Received',
                'category' => 'payment',
                'subject' => 'Payment Received - Invoice #{invoice_number}',
                'body' => "Hi {tenant_name},\n\nWe have received your payment. Thank you!\n\nInvoice Number: {invoice_number}\nAmount: {amount}\nPayment Method: {payment_method}\nDate: {payment_date}\n\nYou can download your invoice from your account dashboard.\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'invoice_number', 'amount', 'payment_method', 'payment_date'],
            ],

            // Payment Failed
            [
                'key' => 'payment_failed',
                'name' => 'Payment Failed',
                'category' => 'payment',
                'subject' => 'Payment Failed - Action Required',
                'body' => "Hi {tenant_name},\n\nWe were unable to process your payment for {app_name}.\n\nAmount: {amount}\nReason: {failure_reason}\n\nPlease update your payment information to avoid service interruption.\n\nUpdate payment: {payment_url}\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'amount', 'failure_reason', 'payment_url'],
            ],

            // Account Suspended
            [
                'key' => 'account_suspended',
                'name' => 'Account Suspended',
                'category' => 'tenant',
                'subject' => 'Account Suspended - {app_name}',
                'body' => "Hi {tenant_name},\n\nYour account has been suspended due to {reason}.\n\nTo reactivate your account, please contact our support team or resolve any outstanding issues.\n\nSupport: {support_email}\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'reason', 'support_email'],
            ],

            // Database Provisioned
            [
                'key' => 'database_provisioned',
                'name' => 'Database Provisioned',
                'category' => 'system',
                'subject' => 'Your Application is Ready - Login Details Inside',
                'body' => "Hi {tenant_name},\n\nGreat news! Your application has been provisioned and is now ready to use.\n\n=== LOGIN DETAILS ===\nApplication URL: {app_url}\nEmail: {email}\nTemporary Password: {password}\n\n⚠️ IMPORTANT SECURITY NOTICE:\nFor your security, please change your password immediately after your first login.\n\nYou can access your account settings after logging in to update your password.\n\n=== GETTING STARTED ===\n1. Click the URL above to access your application\n2. Log in with the credentials provided\n3. Change your password in Account Settings\n4. Start exploring your new system!\n\nIf you need any assistance, please don't hesitate to contact our support team.\n\nBest regards,\n{app_name} Team",
                'variables' => ['tenant_name', 'app_name', 'app_url', 'email', 'password'],
            ],
        ];

        foreach ($templates as $template) {
            SaasNotificationTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }

        $this->command->info('SaaS notification templates seeded successfully!');
    }
}
