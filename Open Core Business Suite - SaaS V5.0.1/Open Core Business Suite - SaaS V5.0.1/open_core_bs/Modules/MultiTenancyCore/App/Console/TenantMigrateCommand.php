<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate
                            {--fresh : Drop all tables and re-run migrations}
                            {--seed : Seed the database after migrating}
                            {--force : Force the operation in production}
                            {--tenant= : Run migration for specific tenant ID}
                            {--admin-email= : Admin email for seeding (default: admin@company.com)}
                            {--admin-password= : Admin password for seeding (default: Admin@123)}
                            {--admin-first-name= : Admin first name (default: Admin)}
                            {--admin-last-name= : Admin last name (default: User)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('database_provisioning_status', 'provisioned')->get();

        if ($tenants->isEmpty()) {
            $this->error('No provisioned tenants found.');

            return 1;
        }

        $this->info("Running migrations for {$tenants->count()} tenant(s)...\n");

        $failed = 0;
        $succeeded = 0;

        foreach ($tenants as $tenant) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info("Tenant: {$tenant->name} ({$tenant->subdomain})");
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            try {
                app(TenantManager::class)->forTenant($tenant, function () use ($tenant) {
                    $options = ['--force' => true];

                    if ($this->option('fresh')) {
                        $this->warn('  → Running migrate:fresh...');
                        Artisan::call('migrate:fresh', $options);
                    } else {
                        $this->info('  → Running migrate...');
                        Artisan::call('migrate', $options);
                    }

                    // Display output
                    $this->line(Artisan::output());

                    if ($this->option('seed')) {
                        $this->info('  → Seeding database with LiveSeeder...');

                        // Get admin credentials from options or use tenant's email
                        $adminEmail = $this->option('admin-email') ?: $tenant->email;
                        $adminPassword = $this->option('admin-password') ?: 'Admin@123';
                        $adminFirstName = $this->option('admin-first-name') ?: 'Admin';
                        $adminLastName = $this->option('admin-last-name') ?: $tenant->name;

                        // Set environment variables for the seeder to use
                        putenv("TENANT_ADMIN_EMAIL={$adminEmail}");
                        putenv("TENANT_ADMIN_PASSWORD={$adminPassword}");
                        putenv("TENANT_ADMIN_FIRST_NAME={$adminFirstName}");
                        putenv("TENANT_ADMIN_LAST_NAME={$adminLastName}");

                        // Run the seeder using Artisan
                        Artisan::call('db:seed', array_merge($options, [
                            '--class' => 'Database\\Seeders\\LiveSeeder',
                        ]));

                        $this->line(Artisan::output());
                        $this->info("  → Admin created: {$adminEmail} / {$adminPassword}");

                        // Clear environment variables
                        putenv('TENANT_ADMIN_EMAIL');
                        putenv('TENANT_ADMIN_PASSWORD');
                        putenv('TENANT_ADMIN_FIRST_NAME');
                        putenv('TENANT_ADMIN_LAST_NAME');
                    }
                });

                $this->info("  ✓ Completed successfully\n");
                $succeeded++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}\n");
                Log::error('Tenant migration failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Migration Summary:');
        $this->line("  ✓ Succeeded: {$succeeded}");

        if ($failed > 0) {
            $this->error("  ✗ Failed: {$failed}");
        }

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return $failed > 0 ? 1 : 0;
    }
}
