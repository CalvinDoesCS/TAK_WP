<?php

namespace Modules\AccountingCore\Database\Seeders;

use App\Services\Settings\ModuleSettingsService;
use Illuminate\Database\Seeder;
use Modules\AccountingCore\App\Settings\AccountingCoreSettings;

class AccountingCoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsService = app(ModuleSettingsService::class);
        $accountingSettings = app(AccountingCoreSettings::class);

        // Get default values from the settings class
        $defaults = $accountingSettings->getDefaults();

        // Seed each default setting
        foreach ($defaults as $key => $value) {
            $settingsService->set('AccountingCore', $key, $value);
        }

        $this->command->info('AccountingCore settings have been seeded with default values.');
    }
}
