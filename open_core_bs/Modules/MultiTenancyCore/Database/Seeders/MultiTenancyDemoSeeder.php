<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use App\Enums\UserAccountStatus;
use App\Models\Designation;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantDatabaseService;

class MultiTenancyDemoSeeder extends Seeder
{
    protected $databaseService;

    public function __construct()
    {
        $this->databaseService = app(TenantDatabaseService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in demo/local environment
        if (! app()->environment(['local', 'demo', 'testing'])) {
            $this->command->warn('Demo seeder should only run in local/demo environment!');

            return;
        }

        $this->command->info('Creating demo tenants...');

        // Demo Tenant 1: Acme Corporation (Active, Premium Plan)
        $this->createTenant([
            'company_name' => 'Acme Corporation',
            'subdomain' => 'acme',
            'email' => 'admin@acme.demo.com',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '1000000101',
            'plan_type' => 'premium',
            'status' => 'active',
            'auto_provision' => true,
            'metadata' => [
                'industry' => 'Corporate',
                'size' => '50-100 employees',
                'website' => 'https://acme.example.com',
            ],
        ]);

        // Demo Tenant 2: Beta Industries (Trial)
        $this->createTenant([
            'company_name' => 'Beta Industries',
            'subdomain' => 'beta',
            'email' => 'admin@beta.demo.com',
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'phone' => '1000000102',
            'plan_type' => 'trial',
            'status' => 'active',
            'auto_provision' => true,
            'metadata' => [
                'industry' => 'Wholesale',
                'size' => '100-200 employees',
                'website' => 'https://beta.example.com',
            ],
        ]);

        $this->command->info('Demo tenants created successfully!');
        $this->command->info('');
        $this->command->info('Demo Tenant Accounts:');
        $this->command->info('1. Acme Corporation');
        $this->command->info('   URL: '.config('app.url'));
        $this->command->info('   Subdomain: acme.'.parse_url(config('app.url'), PHP_URL_HOST));
        $this->command->info('   Email: admin@acme.demo.com');
        $this->command->info('   Password: password123');
        $this->command->info('');
        $this->command->info('2. Beta Industries');
        $this->command->info('   URL: '.config('app.url'));
        $this->command->info('   Subdomain: beta.'.parse_url(config('app.url'), PHP_URL_HOST));
        $this->command->info('   Email: admin@beta.demo.com');
        $this->command->info('   Password: password123');
        $this->command->info('');
        $this->command->info('Demo Employees (per tenant):');
        $this->command->info('   HR: hr_demo@{subdomain}.demo.com');
        $this->command->info('   Office: office1_demo@{subdomain}.demo.com, office2_demo@{subdomain}.demo.com');
        $this->command->info('   Field: field1_demo@{subdomain}.demo.com, field2_demo@{subdomain}.demo.com, field3_demo@{subdomain}.demo.com');
        $this->command->info('   Password for all employees: password123');
    }

    /**
     * Create a demo tenant with all related data
     */
    protected function createTenant(array $data): void
    {
        $this->command->info("Creating tenant: {$data['company_name']}...");

        // Check if tenant already exists
        $existingTenant = Tenant::where('subdomain', $data['subdomain'])->first();
        if ($existingTenant) {
            $this->command->warn("Tenant {$data['company_name']} already exists, skipping...");

            return;
        }

        // Check if user already exists
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            $this->command->warn("User {$data['email']} already exists, skipping tenant creation...");

            return;
        }

        // Generate tenant code
        $lastTenant = User::withTrashed()
            ->where('code', 'LIKE', 'TENANT-%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTenant && preg_match('/TENANT-(\d+)/', $lastTenant->code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $tenantCode = 'TENANT-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Create user account
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => 'male',
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => bcrypt('password123'), // Demo password
            'email_verified_at' => now(),
            'code' => $tenantCode,
        ]);

        // Assign tenant role
        $user->assignRole('tenant');

        // Create tenant record
        $tenant = Tenant::create([
            'name' => $data['company_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subdomain' => $data['subdomain'],
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'active' ? now() : null,
            'approved_by_id' => $data['status'] === 'active' ? 1 : null, // Assuming admin user ID is 1
            'metadata' => $data['metadata'] ?? [],
            'address' => '123 Demo Street',
            'city' => 'Demo City',
            'state' => 'Demo State',
            'country' => 'Demo Country',
            'postal_code' => '12345',
            'website' => $data['metadata']['website'] ?? null,
        ]);

        // Get or create plan
        $plan = $this->getOrCreatePlan($data['plan_type']);

        // Create subscription
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $data['plan_type'] === 'trial' ? 'trial' : 'active',
            'starts_at' => now(),
            'ends_at' => $data['plan_type'] === 'trial'
                ? now()->addDays(14)
                : now()->addDays(30),
            'trial_ends_at' => $data['plan_type'] === 'trial'
                ? now()->addDays(14)
                : null,
            'amount' => $plan->price,
            'currency' => 'SAR',
            'metadata' => [
                'demo' => true,
                'created_via' => 'seeder',
            ],
        ]);

        // Create payment record for non-trial plans
        if ($data['plan_type'] !== 'trial') {
            // Generate invoice number
            $year = date('Y');
            $month = date('m');
            $invoiceNumber = "INV-{$year}{$month}-".str_pad($tenant->id, 5, '0', STR_PAD_LEFT);

            Payment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'currency' => 'SAR',
                'status' => 'approved',
                'payment_method' => 'demo',
                'gateway_transaction_id' => 'DEMO-'.strtoupper(uniqid()),
                'invoice_number' => $invoiceNumber,
                'paid_at' => now(),
                'approved_at' => now(),
                'approved_by_id' => 1,
                'description' => "Payment for {$plan->name} plan",
                'metadata' => [
                    'demo' => true,
                ],
            ]);
        }

        // Auto-provision database if requested
        if ($data['auto_provision'] ?? false) {
            $this->command->info("  Provisioning database for {$data['company_name']}...");

            try {
                // Create database
                $result = $this->databaseService->createDatabase($tenant);

                if ($result['success']) {
                    $this->command->info("  Database created: {$result['database_name']}");

                    // Run migrations and seeders
                    $this->command->info('  Running migrations and seeders...');
                    $this->databaseService->migrateAndSeed($tenant);

                    // Update tenant status to provisioned AFTER successful setup
                    $tenant->update([
                        'database_provisioning_status' => 'provisioned',
                    ]);

                    $this->command->info('  Database setup completed!');

                    // Seed demo employees in tenant database
                    $this->seedDemoEmployees($tenant);
                } else {
                    throw new \Exception($result['message'] ?? 'Unknown error');
                }
            } catch (\Exception $e) {
                $this->command->error('  Failed to provision database: '.$e->getMessage());
                Log::error("Tenant provisioning failed for {$tenant->id}: ".$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                $tenant->update([
                    'database_provisioning_status' => 'failed',
                ]);
            }
        }

        $this->command->info("  Tenant {$data['company_name']} created successfully!");
    }

    /**
     * Get or create a plan based on type
     */
    protected function getOrCreatePlan(string $type): Plan
    {
        // Try to find existing plan
        if ($type === 'trial') {
            $plan = Plan::where('name', 'Starter')->first();
            if ($plan) {
                return $plan;
            }

            // Create trial/starter plan
            return Plan::create([
                'name' => 'Starter',
                'description' => 'Perfect for small businesses and startups',
                'price' => 0, // Free trial
                'billing_period' => 'monthly',
                'is_active' => true,
                'restrictions' => [
                    'max_users' => 5,
                    'max_employees' => 10,
                    'max_storage_gb' => 2,
                    'modules' => [
                        'Payroll',
                        'Attendance',
                        'Leave',
                        'TaskSystem',
                    ],
                ],
            ]);
        }

        // For premium/professional plan
        $plan = Plan::where('name', 'Professional')->first();
        if ($plan) {
            return $plan;
        }

        // Create professional plan
        return Plan::create([
            'name' => 'Professional',
            'description' => 'Great for growing companies with advanced needs',
            'price' => 1499,
            'billing_period' => 'monthly',
            'is_active' => true,
            'restrictions' => [
                'max_users' => 50,
                'max_employees' => 100,
                'max_storage_gb' => 25,
                'modules' => [
                    'Payroll',
                    'Attendance',
                    'Leave',
                    'TaskSystem',
                    'Calendar',
                    'DocumentManagement',
                    'PMCore',
                    'Recruitment',
                    'LoanManagement',
                    'AccountingCore',
                    'ExpenseManagement',
                ],
            ],
        ]);
    }

    /**
     * Seed demo employees in tenant database
     */
    protected function seedDemoEmployees(Tenant $tenant): void
    {
        $this->command->info("  Creating demo employees for {$tenant->name}...");

        try {
            // Set up tenant database connection
            $connectionName = 'tenant_'.$tenant->id;
            $connectionConfig = $this->databaseService->getTenantConnectionConfig($tenant);
            config(['database.connections.'.$connectionName => $connectionConfig]);

            // Purge and reconnect to ensure fresh connection
            DB::purge($connectionName);
            DB::reconnect($connectionName);

            // Store original connection and switch to tenant
            $originalConnection = config('database.default');
            config(['database.default' => $connectionName]);

            try {
                // Get required references from tenant database
                $shift = Shift::on($connectionName)->where('is_default', true)->first();
                $team = Team::on($connectionName)->first();
                $adminUser = User::on($connectionName)->where('code', 'EMP-001')->first();

                // Get designations
                $hrDesignation = Designation::on($connectionName)->where('code', 'DES-002')->first();
                $adminDesignation = Designation::on($connectionName)->where('code', 'DES-001')->first();
                $hrExecutiveDesignation = Designation::on($connectionName)->where('code', 'DES-003')->first();
                $employeeDesignation = Designation::on($connectionName)->where('code', 'DES-004')->first();

                if (! $shift || ! $team) {
                    $this->command->warn('  Missing shift or team in tenant database, skipping employee creation');

                    return;
                }

                $subdomain = $tenant->subdomain;

                // Define demo employees
                $employees = [
                    // HR Employee
                    [
                        'first_name' => 'Helena',
                        'last_name' => 'HR',
                        'email' => "hr_demo@{$subdomain}.demo.com",
                        'phone' => '2000000001',
                        'code' => 'EMP-002',
                        'role' => 'hr',
                        'designation_id' => $hrDesignation?->id ?? $adminDesignation?->id,
                    ],
                    // Office Employees
                    [
                        'first_name' => 'Oscar',
                        'last_name' => 'Office',
                        'email' => "office1_demo@{$subdomain}.demo.com",
                        'phone' => '2000000002',
                        'code' => 'EMP-003',
                        'role' => 'manager',
                        'designation_id' => $adminDesignation?->id,
                    ],
                    [
                        'first_name' => 'Olivia',
                        'last_name' => 'Office',
                        'email' => "office2_demo@{$subdomain}.demo.com",
                        'phone' => '2000000003',
                        'code' => 'EMP-004',
                        'role' => 'office_employee',
                        'designation_id' => $hrExecutiveDesignation?->id ?? $employeeDesignation?->id,
                    ],
                    // Field Employees
                    [
                        'first_name' => 'Frank',
                        'last_name' => 'Field',
                        'email' => "field1_demo@{$subdomain}.demo.com",
                        'phone' => '2000000004',
                        'code' => 'EMP-005',
                        'role' => 'field_employee',
                        'designation_id' => $employeeDesignation?->id,
                    ],
                    [
                        'first_name' => 'Fiona',
                        'last_name' => 'Field',
                        'email' => "field2_demo@{$subdomain}.demo.com",
                        'phone' => '2000000005',
                        'code' => 'EMP-006',
                        'role' => 'field_employee',
                        'designation_id' => $employeeDesignation?->id,
                    ],
                    [
                        'first_name' => 'Felix',
                        'last_name' => 'Field',
                        'email' => "field3_demo@{$subdomain}.demo.com",
                        'phone' => '2000000006',
                        'code' => 'EMP-007',
                        'role' => 'field_employee',
                        'designation_id' => $employeeDesignation?->id,
                    ],
                ];

                $createdCount = 0;
                foreach ($employees as $employeeData) {
                    // Check if employee already exists
                    $existing = User::on($connectionName)->where('email', $employeeData['email'])->first();
                    if ($existing) {
                        continue;
                    }

                    // Create employee in tenant database
                    $employee = new User;
                    $employee->setConnection($connectionName);
                    $employee->fill([
                        'first_name' => $employeeData['first_name'],
                        'last_name' => $employeeData['last_name'],
                        'email' => $employeeData['email'],
                        'phone' => $employeeData['phone'],
                        'password' => bcrypt('password123'),
                        'code' => $employeeData['code'],
                        'gender' => 'male',
                        'status' => UserAccountStatus::ACTIVE,
                        'email_verified_at' => now(),
                        'phone_verified_at' => now(),
                        'remember_token' => Str::random(10),
                        'shift_id' => $shift->id,
                        'team_id' => $team->id,
                        'designation_id' => $employeeData['designation_id'],
                        'date_of_joining' => now()->subMonths(rand(3, 12)),
                        'reporting_to_id' => $adminUser?->id,
                    ]);
                    $employee->save();

                    // Assign role
                    $employee->assignRole($employeeData['role']);

                    $createdCount++;
                }

                $this->command->info("  Created {$createdCount} demo employees");

            } finally {
                // Always restore the original connection
                config(['database.default' => $originalConnection]);
            }

        } catch (\Exception $e) {
            $this->command->error('  Failed to create demo employees: '.$e->getMessage());
            Log::error("Failed to create demo employees for tenant {$tenant->id}: ".$e->getMessage());
        }
    }
}
