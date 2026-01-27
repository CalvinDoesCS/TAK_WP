<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Settings;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LiveSeeder extends Seeder
{
    private string $adminEmail;

    private string $adminPassword;

    private ?string $adminPasswordHash = null;

    private string $adminFirstName;

    private string $adminLastName;

    /**
     * Run the database seeds for production environment
     */
    public function run(): void
    {
        // Read from runtime environment variables (set by TenantMigrateCommand via putenv)
        // or use defaults for standalone mode
        $this->adminEmail = getenv('TENANT_ADMIN_EMAIL') ?: 'admin@company.com';
        $this->adminPassword = getenv('TENANT_ADMIN_PASSWORD') ?: 'Admin@123';
        $this->adminPasswordHash = getenv('TENANT_ADMIN_PASSWORD_HASH') ?: null;
        $this->adminFirstName = getenv('TENANT_ADMIN_FIRST_NAME') ?: 'Admin';
        $this->adminLastName = getenv('TENANT_ADMIN_LAST_NAME') ?: 'User';
        $this->command->info('🌱 Seeding production data...');

        // Seed roles and permissions
        $this->call([
            RoleSeeder::class,
            HRCorePermissionSeeder::class,
        ]);

        // Seed essential MultiTenancyCore data if module is enabled (required for SaaS functionality)
        $this->seedMultiTenancyCoreEssentials();

        // Seed essential organizational data
        $this->seedEssentialData();

        // Leave types and holidays are company-specific - add via admin panel

        // Seed application settings
        $this->seedSettings();

        $this->command->info('✅ Production data seeded successfully!');
    }

    /**
     * Seed essential organizational data
     */
    private function seedEssentialData(): void
    {
        // Create default team
        $team = Team::create([
            'name' => 'Default Team',
            'code' => 'TM-001',
            'status' => Status::ACTIVE,
            'is_chat_enabled' => true,
        ]);
        $this->command->info('✓ Default team created');

        // Create default shift (9 AM - 6 PM, Monday-Friday)
        $shift = Shift::create([
            'name' => 'Default Shift',
            'code' => 'SH-001',
            'status' => Status::ACTIVE,
            'start_date' => now(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_default' => true,
            'sunday' => false,
            'monday' => true,
            'tuesday' => true,
            'wednesday' => true,
            'thursday' => true,
            'friday' => true,
            'saturday' => false,
        ]);
        $this->command->info('✓ Default shift created');

        // Expense types are company-specific - add via admin panel

        // Create essential departments and designations
        $this->seedDepartmentsAndDesignations();

        // Get the admin designation
        $adminDesignation = Designation::where('code', 'DES-001')->first();

        // Create default admin user
        // Use password hash directly if provided (from tenant registration), otherwise bcrypt the password
        $admin = User::factory()->create([
            'first_name' => $this->adminFirstName,
            'last_name' => $this->adminLastName,
            'email' => $this->adminEmail,
            'phone' => '0000000000',
            'phone_verified_at' => now(),
            'password' => $this->adminPasswordHash ?: bcrypt($this->adminPassword),
            'code' => 'EMP-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $team->id,
            'designation_id' => $adminDesignation->id,
        ]);
        $admin->assignRole('admin');

        $this->command->info('✓ Admin user created');
    }

    /**
     * Seed essential departments and designations
     */
    private function seedDepartmentsAndDesignations(): void
    {
        // Default Department (required for admin user)
        $defaultDepartment = Department::create([
            'name' => 'General',
            'code' => 'DEPT-001',
            'notes' => 'Default department',
        ]);

        // Default Designation (required for admin user)
        Designation::create([
            'name' => 'Administrator',
            'code' => 'DES-001',
            'department_id' => $defaultDepartment->id,
            'notes' => 'System administrator',
        ]);

        $this->command->info('✓ Default department and designation created');
    }

    /**
     * Seed application settings with generic data
     */
    private function seedSettings(): void
    {
        Settings::create([
            'website' => 'https://yourcompany.com',
            'support_email' => 'support@yourcompany.com',
            'support_phone' => '+1234567890',
            'support_whatsapp' => '+1234567890',
            'company_name' => 'Your Company Name',
            'company_logo' => 'app_logo.png',
            'company_address' => 'Your Company Address',
            'company_phone' => '+1234567890',
            'company_email' => 'info@yourcompany.com',
            'company_website' => 'https://yourcompany.com',
            'company_country' => 'Your Country',
            'company_state' => 'Your State',
            'company_city' => 'Your City',
            'company_zipcode' => '000000',
            'company_tax_id' => 'TAX-ID-HERE',
            'company_reg_no' => 'REG-NO-HERE',
        ]);

        $this->command->info('✓ Settings created (please update with your company information)');
    }

    /**
     * Seed essential MultiTenancyCore data for SaaS functionality.
     * Only runs on main database, NOT on tenant databases.
     * These seeders are idempotent and safe to run multiple times.
     */
    private function seedMultiTenancyCoreEssentials(): void
    {
        // Skip if running in tenant database context (tenant provisioning mode)
        // When TENANT_ADMIN_EMAIL is set, we're seeding a tenant database which doesn't have MultiTenancyCore tables
        if (getenv('TENANT_ADMIN_EMAIL')) {
            $this->command->info('Skipping MultiTenancyCore seeders (tenant database context)');

            return;
        }

        // Check if MultiTenancyCore module is available
        if (! class_exists(\Modules\MultiTenancyCore\Database\Seeders\TenantRoleSeeder::class)) {
            return;
        }

        $this->command->info('Seeding MultiTenancyCore essentials...');

        // 1. Tenant role and permissions (required for tenant registration)
        $this->call(\Modules\MultiTenancyCore\Database\Seeders\TenantRoleSeeder::class);

        // 2. SaaS settings (registration, trial, payment gateways)
        $this->call(\Modules\MultiTenancyCore\Database\Seeders\SaasSettingsSeeder::class);

        // 3. Notification templates (email templates for tenants)
        $this->call(\Modules\MultiTenancyCore\Database\Seeders\SaasNotificationTemplatesSeeder::class);

        $this->command->info('✓ MultiTenancyCore essentials seeded');
    }
}
