<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class TenantMigrateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-status {--tenant= : Check specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show migration status for all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('database_provisioning_status', 'provisioned')->get();

        $this->info("Migration Status for {$tenants->count()} tenant(s):\n");

        foreach ($tenants as $tenant) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Tenant: {$tenant->name} ({$tenant->subdomain})");
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            try {
                app(TenantManager::class)->forTenant($tenant, function () {
                    Artisan::call('migrate:status');
                    $this->line(Artisan::output());
                });
            } catch (\Exception $e) {
                $this->error("  ✗ Error: {$e->getMessage()}");
            }

            $this->newLine();
        }

        return 0;
    }
}
