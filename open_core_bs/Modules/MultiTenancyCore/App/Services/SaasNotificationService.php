<?php

namespace Modules\MultiTenancyCore\App\Services;

use App\Helpers\FormattingHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\MultiTenancyCore\App\Models\SaasNotificationTemplate;

class SaasNotificationService
{
    /**
     * Send notification using template
     */
    public function sendNotification($templateKey, $recipient, $data = [])
    {
        $template = SaasNotificationTemplate::getByKey($templateKey);

        if (! $template) {
            Log::warning("Notification template not found: {$templateKey}");

            return false;
        }

        $parsed = $template->parse($data);

        try {
            Mail::raw($parsed['body'], function ($message) use ($recipient, $parsed) {
                $message->to($recipient)
                    ->subject($parsed['subject']);
            });

            Log::info("Notification sent: {$templateKey} to {$recipient}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send notification: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Send welcome email to new tenant
     */
    public function sendWelcomeEmail($tenant)
    {
        $data = [
            'tenant_name' => $tenant->name,
            'app_name' => config('app.name'),
            'company_name' => $tenant->name,
            'email' => $tenant->email,
            'subdomain' => $tenant->subdomain,
            'login_url' => route('login'),
        ];

        return $this->sendNotification('tenant_welcome', $tenant->email, $data);
    }

    /**
     * Send payment received notification
     */
    public function sendPaymentReceivedEmail($payment)
    {
        $tenant = $payment->tenant;

        $data = [
            'tenant_name' => $tenant->name,
            'app_name' => config('app.name'),
            'invoice_number' => $payment->invoice_number,
            'amount' => FormattingHelper::formatCurrency($payment->amount),
            'payment_method' => ucfirst($payment->payment_method),
            'payment_date' => $payment->paid_at ? $payment->paid_at->format('M d, Y') : now()->format('M d, Y'),
        ];

        return $this->sendNotification('payment_received', $tenant->email, $data);
    }

    /**
     * Send trial expiring notification
     */
    public function sendTrialExpiringEmail($tenant, $daysRemaining)
    {
        $data = [
            'tenant_name' => $tenant->name,
            'app_name' => config('app.name'),
            'days' => $daysRemaining,
            'expiry_date' => $tenant->trial_ends_at->format('M d, Y'),
            'upgrade_url' => route('multitenancycore.tenant.subscription'),
        ];

        return $this->sendNotification('trial_expiring', $tenant->email, $data);
    }

    /**
     * Send provisioning complete email
     *
     * Note: The tenant uses their registration password, so no password is sent.
     */
    public function sendProvisioningCompleteEmail($tenant)
    {
        $data = [
            'tenant_name' => $tenant->name,
            'app_name' => config('app.name'),
            'company_name' => $tenant->name,
            'email' => $tenant->email,
            'subdomain' => $tenant->subdomain,
            'login_url' => route('login'),
        ];

        return $this->sendNotification('tenant_provisioning_complete', $tenant->email, $data);
    }
}
