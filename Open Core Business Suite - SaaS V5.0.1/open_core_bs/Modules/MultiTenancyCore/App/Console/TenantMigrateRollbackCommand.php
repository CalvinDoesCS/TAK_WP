<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class TenantMigrateRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-rollback
                            {--step= : Number of migrations to rollback}
                            {--force : Force the operation in production}
                            {--tenant= : Run rollback for specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations for all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->option('force') && ! $this->confirm('This will rollback migrations for all tenants. Continue?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('database_provisioning_status', 'provisioned')->get();

        $this->info("Rolling back migrations for {$tenants->count()} tenant(s)...\n");

        foreach ($tenants as $tenant) {
            $this->info("Tenant: {$tenant->subdomain}");

            try {
                app(TenantManager::class)->forTenant($tenant, function () {
                    $options = ['--force' => true];

                    if ($this->option('step')) {
                        $options['--step'] = $this->option('step');
                    }

                    Artisan::call('migrate:rollback', $options);
                    $this->line(Artisan::output());
                });

                $this->info("  ✓ Completed\n");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}\n");
            }
        }

        $this->info('Rollback completed!');

        return 0;
    }
}
