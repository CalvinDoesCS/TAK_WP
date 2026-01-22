<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update tenant role
        $tenantRole = Role::where('name', 'tenant')->first();
        if (! $tenantRole) {
            $tenantRole = Role::create([
                'name' => 'tenant',
                'guard_name' => 'web',
                'description' => 'Role for tenant users',
                'is_web_access_enabled' => true,  // Tenant users need web access for the tenant portal
                'is_mobile_app_access_enabled' => false,
            ]);
        } else {
            $tenantRole->update([
                'description' => 'Role for tenant users',
                'is_web_access_enabled' => true,  // Ensure existing tenant roles have web access
            ]);
        }

        // Define tenant permissions
        $tenantPermissions = [
            // Tenant portal permissions
            'tenant.dashboard.view',
            'tenant.subscription.view',
            'tenant.subscription.change-plan',
            'tenant.payment.view',
            'tenant.payment.make',
            'tenant.invoice.view',
            'tenant.invoice.download',
            'tenant.profile.view',
            'tenant.profile.update',
            'tenant.usage.view',
            'tenant.support.create',

            // Database info permissions
            'tenant.database.view-info',
            'tenant.database.download-credentials',
        ];

        // Create permissions if they don't exist
        foreach ($tenantPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Assign permissions to tenant role
        $tenantRole->syncPermissions($tenantPermissions);

        // Also ensure super-admin has these permissions
        $superAdminRole = Role::findByName('super_admin');
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($tenantPermissions);
        }
    }
}
