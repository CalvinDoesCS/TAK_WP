<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('Seeding Roles...');

        Role::create(['name' => 'super_admin', 'guard_name' => 'web', 'is_web_access_enabled' => true, 'is_mobile_app_access_enabled' => true]);
        Role::create(['name' => 'admin', 'guard_name' => 'web', 'is_web_access_enabled' => true, 'is_mobile_app_access_enabled' => true]);
        Role::create(['name' => 'hr', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_web_access_enabled' => true, 'is_multiple_check_in_enabled' => true]);
        Role::create(['name' => 'hr_manager', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_web_access_enabled' => true, 'is_multiple_check_in_enabled' => true]);
        Role::create(['name' => 'hr_executive', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_web_access_enabled' => true, 'is_multiple_check_in_enabled' => true]);
        Role::create(['name' => 'team_leader', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_web_access_enabled' => true]);
        Role::create(['name' => 'field_employee', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_web_access_enabled' => false, 'is_location_activity_tracking_enabled' => true, 'is_multiple_check_in_enabled' => true]);
        Role::create(['name' => 'office_employee', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_multiple_check_in_enabled' => true]);
        Role::create(['name' => 'manager', 'guard_name' => 'web', 'is_mobile_app_access_enabled' => true, 'is_multiple_check_in_enabled' => true]);

        $this->command->info('Roles Seeded Successfully.');

    }
}
