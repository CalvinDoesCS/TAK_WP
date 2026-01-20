<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class ModuleToggleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:toggle {action : enable-all or disable-all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable or disable all modules except core modules for testing';

    /**
     * Core modules that should never be disabled
     */
    protected array $coreModules = [
        'AccountingCore',
        'MultiTenancyCore',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['enable-all', 'disable-all'])) {
            $this->error('Invalid action. Use "enable-all" or "disable-all"');

            return self::FAILURE;
        }

        if ($action === 'disable-all') {
            return $this->disableAllModules();
        }

        return $this->enableAllModules();
    }

    /**
     * Disable all modules except core modules
     */
    protected function disableAllModules(): int
    {
        $this->info('Disabling all modules except core modules...');
        $this->newLine();

        $modules = Module::all();
        $disabled = 0;
        $skipped = 0;

        foreach ($modules as $module) {
            $moduleName = $module->getName();

            if (in_array($moduleName, $this->coreModules)) {
                $this->line("⊘ Skipping core module: <fg=yellow>{$moduleName}</>");
                $skipped++;

                continue;
            }

            if ($module->isEnabled()) {
                Module::disable($moduleName);
                $this->line("✓ Disabled: <fg=red>{$moduleName}</>");
                $disabled++;
            } else {
                $this->line("- Already disabled: <fg=gray>{$moduleName}</>");
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Disabled: {$disabled}");
        $this->line("  Skipped (core): {$skipped}");
        $this->line('  Total modules: '.count($modules));

        return self::SUCCESS;
    }

    /**
     * Enable all modules
     */
    protected function enableAllModules(): int
    {
        $this->info('Enabling all modules...');
        $this->newLine();

        $modules = Module::all();
        $enabled = 0;

        foreach ($modules as $module) {
            $moduleName = $module->getName();

            if ($module->isDisabled()) {
                Module::enable($moduleName);
                $this->line("✓ Enabled: <fg=green>{$moduleName}</>");
                $enabled++;
            } else {
                $this->line("- Already enabled: <fg=gray>{$moduleName}</>");
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Enabled: {$enabled}");
        $this->line('  Total modules: '.count($modules));

        return self::SUCCESS;
    }
}
