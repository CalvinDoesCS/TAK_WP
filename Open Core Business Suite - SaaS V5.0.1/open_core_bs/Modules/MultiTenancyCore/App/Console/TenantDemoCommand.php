<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:demo 
                            {--fresh : Drop existing demo tenants and recreate}
                            {--provision : Auto-provision databases for demo tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create demo tenants for testing multi-tenancy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!app()->environment(['local', 'demo', 'testing'])) {
            $this->error('This command can only run in local/demo environment!');
            return 1;
        }

        $this->info('Setting up demo tenants for testing...');
        
        if ($this->option('fresh')) {
            $this->warn('This will delete existing demo tenants. Are you sure?');
            if (!$this->confirm('Do you wish to continue?')) {
                return 0;
            }
            
            $this->cleanupDemoTenants();
        }

        // Run the demo seeder
        $this->info('Creating demo tenants...');
        Artisan::call('module:seed', [
            'module' => 'MultiTenancyCore',
            '--class' => 'MultiTenancyDemoSeeder',
        ]);
        
        $this->info(Artisan::output());
        
        $this->displayAccessInfo();
        
        return 0;
    }
    
    /**
     * Clean up existing demo tenants
     */
    protected function cleanupDemoTenants(): void
    {
        $this->info('Cleaning up existing demo tenants...');
        
        // Delete demo tenant users
        \App\Models\User::whereIn('email', [
            'admin@acme.demo',
            'admin@beta.demo'
        ])->forceDelete();
        
        // Delete demo tenants
        \Modules\MultiTenancyCore\App\Models\Tenant::whereIn('subdomain', [
            'acme',
            'beta'
        ])->forceDelete();
        
        // Drop demo databases if they exist
        try {
            DB::statement("DROP DATABASE IF EXISTS `tenant_1_acme`");
            DB::statement("DROP DATABASE IF EXISTS `tenant_2_beta`");
            DB::statement("DROP USER IF EXISTS 'user_1'@'localhost'");
            DB::statement("DROP USER IF EXISTS 'user_1'@'%'");
            DB::statement("DROP USER IF EXISTS 'user_2'@'localhost'");
            DB::statement("DROP USER IF EXISTS 'user_2'@'%'");
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        $this->info('Cleanup completed!');
    }
    
    /**
     * Display access information
     */
    protected function displayAccessInfo(): void
    {
        $this->info('');
        $this->info('========================================');
        $this->info('Demo Tenants Created Successfully!');
        $this->info('========================================');
        $this->info('');
        
        $appUrl = config('app.url');
        $domain = parse_url($appUrl, PHP_URL_HOST);
        
        $this->table(
            ['Tenant', 'URL', 'Email', 'Password'],
            [
                [
                    'Acme Corporation',
                    'http://acme.' . $domain,
                    'admin@acme.demo',
                    '123456'
                ],
                [
                    'Beta Industries', 
                    'http://beta.' . $domain,
                    'admin@beta.demo',
                    '123456'
                ]
            ]
        );
        
        $this->info('');
        $this->warn('Note: Make sure to configure your web server to handle wildcard subdomains.');
        $this->warn('For local development, add these entries to your hosts file:');
        $this->info('127.0.0.1   acme.' . $domain);
        $this->info('127.0.0.1   beta.' . $domain);
    }
}