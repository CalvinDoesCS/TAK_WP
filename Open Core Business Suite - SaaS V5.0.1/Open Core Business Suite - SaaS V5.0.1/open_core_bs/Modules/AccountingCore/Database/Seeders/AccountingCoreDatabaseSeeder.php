<?php

namespace Modules\AccountingCore\Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingCoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Essential seeders - always run
        $this->call([
            AccountingCorePermissionSeeder::class,
            \Modules\AccountingCore\Database\Seeders\AccountingCoreSettingsSeeder::class,
        ]);

        // Only run demo seeders in local/demo/testing environment
        if (app()->environment(['local', 'demo', 'testing'])) {
            $this->call([
                \Modules\AccountingCore\Database\Seeders\TaxRateSeeder::class,
                AccountingCoreDemoSeeder::class,
            ]);
        }
    }
}
