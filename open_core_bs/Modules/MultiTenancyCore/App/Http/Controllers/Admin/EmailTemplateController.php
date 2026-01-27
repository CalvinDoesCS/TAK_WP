<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\MultiTenancyCore\App\Models\SaasNotificationTemplate;

class EmailTemplateController extends Controller
{
    /**
     * Display email templates list
     */
    public function index()
    {
        $templates = SaasNotificationTemplate::orderBy('category')->orderBy('name')->get();
        $categories = [
            'tenant' => __('Tenant Management'),
            'subscription' => __('Subscriptions'),
            'payment' => __('Payments'),
            'system' => __('System'),
        ];

        return view('multitenancycore::admin.email-templates.index', compact('templates', 'categories'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $template = SaasNotificationTemplate::findOrFail($id);

        return view('multitenancycore::admin.email-templates.edit', compact('template'));
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = SaasNotificationTemplate::findOrFail($id);

        $template->update([
            'subject' => $request->subject,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Email template updated successfully'),
        ]);
    }

    /**
     * Test email template
     */
    public function test(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $template = SaasNotificationTemplate::findOrFail($id);

        // Create sample data for testing
        $sampleData = $this->getSampleData($template->key);

        try {
            $parsed = $template->parse($sampleData);

            Mail::raw($parsed['body'], function ($message) use ($request, $parsed) {
                $message->to($request->email)
                    ->subject('[TEST] '.$parsed['subject']);
            });

            return response()->json([
                'status' => 'success',
                'message' => __('Test email sent successfully to :email', ['email' => $request->email]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Failed to send test email: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Get sample data for testing templates
     */
    private function getSampleData($templateKey)
    {
        $appName = config('app.name');

        $commonData = [
            'tenant_name' => 'John Doe',
            'app_name' => $appName,
            'company_name' => 'Acme Corporation',
            'email' => 'john@example.com',
            'support_email' => 'support@'.parse_url(config('app.url'), PHP_URL_HOST),
        ];

        $specificData = [
            'tenant_welcome' => [
                'subdomain' => 'acme',
                'login_url' => route('login'),
            ],
            'trial_expiring' => [
                'days' => '7',
                'expiry_date' => now()->addDays(7)->format('M d, Y'),
                'upgrade_url' => route('login'),
            ],
            'subscription_created' => [
                'plan_name' => 'Professional Plan',
                'billing_cycle' => 'Monthly',
                'amount' => '$99.00',
                'next_billing_date' => now()->addMonth()->format('M d, Y'),
            ],
            'payment_received' => [
                'invoice_number' => 'INV-2024-00123',
                'amount' => '$99.00',
                'payment_method' => 'Credit Card',
                'payment_date' => now()->format('M d, Y'),
            ],
            'payment_failed' => [
                'amount' => '$99.00',
                'failure_reason' => 'Insufficient funds',
                'payment_url' => route('login'),
            ],
            'account_suspended' => [
                'reason' => 'overdue payment',
            ],
            'database_provisioned' => [
                'app_url' => 'https://acme.'.parse_url(config('app.url'), PHP_URL_HOST),
            ],
        ];

        return array_merge($commonData, $specificData[$templateKey] ?? []);
    }
}
