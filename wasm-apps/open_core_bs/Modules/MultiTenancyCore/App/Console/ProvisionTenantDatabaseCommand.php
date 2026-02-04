<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantDatabaseService;

class ProvisionTenantDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:provision-database
                            {tenant : The ID or subdomain of the tenant}
                            {--demo : Seed demo/sample data (for local testing only)}';

    /**
     * The console command description.
     */
    protected $description = 'Provision database for a specific tenant';

    protected $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        parent::__construct();
        $this->databaseService = $databaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('tenant');

        // Find tenant by ID or subdomain
        $tenant = is_numeric($identifier)
            ? Tenant::find($identifier)
            : Tenant::where('subdomain', $identifier)->first();

        if (! $tenant) {
            $this->error("Tenant not found: {$identifier}");

            return 1;
        }

        $seedDemo = $this->option('demo');

        $this->info("Provisioning database for tenant: {$tenant->name} ({$tenant->subdomain})");

        if ($seedDemo) {
            $this->warn('⚠️  Demo mode enabled - sample data will be seeded (for local testing only)');
        }

        try {
            // Create database
            $result = $this->databaseService->createDatabase($tenant);

            if ($result['success']) {
                $this->info("Database created: {$result['database_name']}");

                // Update tenant status
                $tenant->update([
                    'database_provisioning_status' => 'provisioned',
                ]);

                // Run migrations and seeders
                $this->info('Running migrations and seeders...');
                $this->databaseService->migrateAndSeed($tenant, $seedDemo);

                $this->info('Database setup completed successfully!');

                if ($seedDemo) {
                    $this->info('✅ Demo data seeded (sites, clients, sample records, etc.)');
                }

                return 0;
            } else {
                throw new \Exception($result['message'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->error('Failed to provision database: '.$e->getMessage());
            $tenant->update([
                'database_provisioning_status' => 'failed',
            ]);

            return 1;
        }
    }
}
