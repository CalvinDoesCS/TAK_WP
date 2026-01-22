<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use Illuminate\Database\Seeder;

class MultiTenancyCoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            \Modules\MultiTenancyCore\Database\Seeders\TenantRoleSeeder::class,
            \Modules\MultiTenancyCore\Database\Seeders\PlanSeeder::class,
            \Modules\MultiTenancyCore\Database\Seeders\SaasSettingsSeeder::class,
            \Modules\MultiTenancyCore\Database\Seeders\SaasNotificationTemplatesSeeder::class,
        ]);

        // Only run demo seeder in local/demo environment
        if (app()->environment(['local', 'demo', 'testing'])) {
            $this->call([
                \Modules\MultiTenancyCore\Database\Seeders\MultiTenancyDemoSeeder::class,
            ]);
        }
    }
}
