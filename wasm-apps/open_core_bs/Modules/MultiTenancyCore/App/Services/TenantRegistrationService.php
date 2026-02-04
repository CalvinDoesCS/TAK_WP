<?php

namespace Modules\MultiTenancyCore\App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Tenant;

/**
 * Service responsible for tenant registration.
 *
 * This service handles the creation of User + Tenant records during registration.
 * It does NOT create subscriptions - subscriptions are created later when
 * the tenant selects a plan.
 */
class TenantRegistrationService
{
    public function __construct(
        protected SaasNotificationService $notificationService,
        protected TenantDatabaseService $databaseService
    ) {}

    /**
     * Register a new tenant (User + Tenant only, NO subscription).
     *
     * Creates the tenant owner user account and the tenant record.
     * The subscription is created separately when the tenant selects a plan.
     *
     * @param  array  $userData  ['first_name', 'last_name', 'gender', 'phone', 'email', 'password']
     * @param  array  $tenantData  ['company_name', 'subdomain']
     * @return array{success: bool, user: ?User, tenant: ?Tenant, message: string}
     */
    public function register(array $userData, array $tenantData): array
    {
        try {
            DB::beginTransaction();

            // Generate unique tenant code
            $tenantCode = $this->generateTenantCode();

            // Determine if email verification is required
            $requireEmailVerification = $this->requiresEmailVerification();

            // Create user account
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'gender' => $userData['gender'],
                'phone' => $userData['phone'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'code' => $tenantCode,
                'email_verified_at' => (! $requireEmailVerification || config('app.demo')) ? now() : null,
            ]);

            // Assign tenant role
            $user->assignRole('tenant');

            // Determine initial tenant status based on auto-approve setting
            $status = $this->determineInitialStatus();

            // Create tenant record
            $tenant = new Tenant;
            $tenant->name = $tenantData['company_name'];
            $tenant->email = $userData['email'];
            $tenant->phone = $userData['phone'];
            $tenant->subdomain = $tenantData['subdomain'];
            $tenant->status = $status;

            // If auto-approved, set approved details
            if ($status === 'active') {
                $tenant->approved_at = now();
                $tenant->approved_by_id = 1; // System approval
            }

            $tenant->save();

            // Store registration metadata
            $tenant->update([
                'metadata' => $this->buildRegistrationMetadata($tenantData),
            ]);

            DB::commit();

            // Handle post-registration actions (emails, provisioning, etc.)
            $this->handlePostRegistration($user, $tenant);

            Log::info('Tenant registered successfully', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'tenant' => $tenant,
                'message' => $this->getSuccessMessage($status, $requireEmailVerification),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Tenant registration failed', [
                'email' => $userData['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'user' => null,
                'tenant' => null,
                'message' => 'Registration failed. Please try again.',
            ];
        }
    }

    /**
     * Generate unique tenant code (TENANT-001, TENANT-002, etc.).
     *
     * Queries the users table to find the last TENANT-XXX code and
     * generates the next sequential code.
     */
    protected function generateTenantCode(): string
    {
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

        return 'TENANT-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Determine initial tenant status based on SaaS settings.
     *
     * Returns 'active' if auto_approve is enabled, 'pending' otherwise.
     */
    protected function determineInitialStatus(): string
    {
        $autoApprove = SaasSetting::get('general_auto_approve_tenants', false);

        return $autoApprove ? 'active' : 'pending';
    }

    /**
     * Handle post-registration actions.
     *
     * Actions performed:
     * - Send verification email if required
     * - Trigger database provisioning if auto-approved AND auto-provisioning enabled
     * - Send welcome/approval pending email
     */
    protected function handlePostRegistration(User $user, Tenant $tenant): void
    {
        // Send email verification if required and not in demo mode
        if ($this->requiresEmailVerification() && ! config('app.demo') && ! $user->email_verified_at) {
            try {
                $user->sendEmailVerificationNotification();
                Log::info("Verification email sent to tenant user {$user->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send verification email: {$e->getMessage()}");
            }
        }

        // Send welcome email or approval pending email based on status
        $this->sendRegistrationEmail($tenant);

        // If auto-approved AND auto-provisioning is enabled, trigger database provisioning
        if ($tenant->status === 'active' && $this->isAutoProvisioningEnabled()) {
            $this->triggerAutoProvisioning($tenant);
        }
    }

    /**
     * Check if email verification is required.
     */
    protected function requiresEmailVerification(): bool
    {
        return SaasSetting::get('general_require_email_verification', true);
    }

    /**
     * Check if auto-provisioning is enabled.
     */
    protected function isAutoProvisioningEnabled(): bool
    {
        return SaasSetting::get('general_tenant_auto_provisioning', false);
    }

    /**
     * Build registration metadata array.
     *
     * @param  array  $tenantData  Additional tenant data that may contain request info
     */
    protected function buildRegistrationMetadata(array $tenantData): array
    {
        return [
            'registration_date' => now()->toDateTimeString(),
            'ip_address' => $tenantData['ip_address'] ?? request()->ip(),
            'user_agent' => $tenantData['user_agent'] ?? request()->userAgent(),
            'registration_source' => $tenantData['source'] ?? 'web',
        ];
    }

    /**
     * Send appropriate registration email based on tenant status.
     */
    protected function sendRegistrationEmail(Tenant $tenant): void
    {
        try {
            if ($tenant->status === 'active') {
                $this->notificationService->sendWelcomeEmail($tenant);
                Log::info("Welcome email sent to tenant {$tenant->id}");
            } else {
                $this->sendApprovalPendingEmail($tenant);
                Log::info("Approval pending email sent to tenant {$tenant->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send registration email to tenant {$tenant->id}: {$e->getMessage()}");

            // Notify admin about email failure
            $this->notifyAdminEmailFailed($tenant, $e->getMessage());
        }
    }

    /**
     * Send approval pending notification email.
     */
    protected function sendApprovalPendingEmail(Tenant $tenant): void
    {
        $data = [
            'tenant_name' => $tenant->name,
            'app_name' => config('app.name'),
            'company_name' => $tenant->name,
            'email' => $tenant->email,
            'subdomain' => $tenant->subdomain,
        ];

        $this->notificationService->sendNotification('tenant_pending_approval', $tenant->email, $data);
    }

    /**
     * Trigger automatic database provisioning for the tenant.
     */
    protected function triggerAutoProvisioning(Tenant $tenant): void
    {
        try {
            // Create database
            $result = $this->databaseService->createDatabase($tenant);

            if ($result['success']) {
                // Run migrations and seeders
                $this->databaseService->migrateAndSeed($tenant);

                // Update provisioning status
                $tenant->update([
                    'database_provisioning_status' => 'provisioned',
                ]);

                Log::info("Auto-provisioned database for tenant: {$tenant->id}");

                // Send credentials email to customer
                $this->sendProvisioningCompleteEmail($tenant);

            } else {
                // Mark provisioning as failed
                $tenant->update([
                    'database_provisioning_status' => 'failed',
                    'status' => 'pending', // Revert to pending for manual intervention
                ]);

                Log::error("Failed to auto-provision database for tenant: {$tenant->id} - {$result['message']}");

                // Send admin notification about failed provisioning
                $this->notifyAdminProvisioningFailed($tenant, $result['message']);
            }
        } catch (\Exception $e) {
            // Mark provisioning as failed and revert tenant to pending status
            $tenant->update([
                'database_provisioning_status' => 'failed',
                'status' => 'pending', // Revert to pending for manual intervention
            ]);

            Log::error("Auto-provisioning error for tenant {$tenant->id}: {$e->getMessage()}");

            // Send admin notification about failed provisioning
            $this->notifyAdminProvisioningFailed($tenant, $e->getMessage());
        }
    }

    /**
     * Send provisioning complete email.
     *
     * Note: The tenant uses their registration password, so no password is sent in the email.
     */
    protected function sendProvisioningCompleteEmail(Tenant $tenant): void
    {
        try {
            $this->notificationService->sendProvisioningCompleteEmail($tenant);

            Log::info("Provisioning complete email sent to tenant {$tenant->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send provisioning email: {$e->getMessage()}");
            $this->notifyAdminEmailFailed($tenant, $e->getMessage());
        }
    }

    /**
     * Notify admin about provisioning failure.
     */
    protected function notifyAdminProvisioningFailed(Tenant $tenant, string $errorMessage): void
    {
        try {
            if (class_exists(AdminAlertService::class)) {
                app(AdminAlertService::class)->notifyProvisioningFailed($tenant, $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send admin alert: {$e->getMessage()}");
        }
    }

    /**
     * Notify admin about email failure.
     */
    protected function notifyAdminEmailFailed(Tenant $tenant, string $errorMessage): void
    {
        try {
            if (class_exists(AdminAlertService::class)) {
                app(AdminAlertService::class)->notifyEmailFailed($tenant, 'registration', $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send admin alert: {$e->getMessage()}");
        }
    }

    /**
     * Get success message based on registration status.
     */
    protected function getSuccessMessage(string $status, bool $requiresVerification): string
    {
        $platformName = SaasSetting::get('general_platform_name', config('app.name'));

        if ($requiresVerification) {
            return 'Registration successful! Please verify your email to continue.';
        }

        if ($status === 'active') {
            return "Registration successful! Welcome to {$platformName}.";
        }

        return 'Registration successful! Your account is pending approval. You will receive an email once approved.';
    }
}
