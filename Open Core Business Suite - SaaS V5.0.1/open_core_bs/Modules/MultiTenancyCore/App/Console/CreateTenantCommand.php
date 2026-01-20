<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Services\TenantDatabaseService;
use Illuminate\Support\Facades\DB;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {name : The tenant company name}
                            {email : The tenant admin email}
                            {--subdomain= : Custom subdomain (optional)}
                            {--plan= : Plan ID (defaults to trial plan)}
                            {--approve : Auto-approve the tenant}
                            {--provision : Auto-provision database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with subscription';

    protected $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        parent::__construct();
        $this->databaseService = $databaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating new tenant...');

        DB::beginTransaction();

        try {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $this->argument('name'),
                'email' => $this->argument('email'),
                'subdomain' => $this->option('subdomain'),
                'status' => $this->option('approve') ? 'active' : 'pending',
                'approved_at' => $this->option('approve') ? now() : null,
            ]);

            $this->info("Tenant created: {$tenant->name} (ID: {$tenant->id})");
            $this->info("Subdomain: {$tenant->subdomain}");

            // Get plan
            $planId = $this->option('plan');
            $plan = $planId ? Plan::find($planId) : Plan::where('is_trial', true)->first();

            if (!$plan) {
                $plan = Plan::first();
            }

            if (!$plan) {
                throw new \Exception('No plans available. Please create a plan first.');
            }

            // Create subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $plan->is_trial ? 'trial' : 'active',
                'starts_at' => now(),
                'ends_at' => $plan->is_trial 
                    ? now()->addDays($plan->trial_days ?? 14)
                    : now()->addDays($plan->duration_days ?? 30),
                'trial_ends_at' => $plan->is_trial ? now()->addDays($plan->trial_days ?? 14) : null,
            ]);

            $this->info("Subscription created: {$plan->name} plan");

            // Provision database if requested
            if ($this->option('provision')) {
                $this->info('Provisioning database...');
                
                $result = $this->databaseService->createDatabase($tenant);
                
                if ($result['success']) {
                    $this->info("Database provisioned: {$result['database_name']}");
                    
                    // Run migrations and seeders
                    $this->info('Running migrations and seeders...');
                    $this->databaseService->migrateAndSeed($tenant);
                    
                    // Update tenant status
                    $tenant->database_provisioning_status = 'provisioned';
                    $tenant->save();
                    
                    $this->info('Database setup completed!');
                } else {
                    throw new \Exception('Database provisioning failed: ' . $result['message']);
                }
            }

            DB::commit();

            $this->info('');
            $this->info('Tenant created successfully!');
            $this->info('');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Tenant ID', $tenant->id],
                    ['Name', $tenant->name],
                    ['Email', $tenant->email],
                    ['Subdomain', $tenant->subdomain],
                    ['Status', $tenant->status],
                    ['Plan', $plan->name],
                    ['Subscription Status', $subscription->status],
                    ['Subscription Ends', $subscription->ends_at->format('Y-m-d')],
                    ['Database Status', $tenant->database_provisioning_status],
                    ['Tenant URL', tenantUrl('/', $tenant)],
                ]
            );

            if (!$this->option('provision')) {
                $this->info('');
                $this->warn('Database not provisioned. Run the following command to provision:');
                $this->warn("php artisan tenant:provision {$tenant->id}");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to create tenant: ' . $e->getMessage());
            return 1;
        }
    }
}