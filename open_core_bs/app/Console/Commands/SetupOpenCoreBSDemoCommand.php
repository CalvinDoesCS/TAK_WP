<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SetupOpenCoreBSDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opencorebs:demo
                            {--fresh : Drop all tables and re-run migrations}
                            {--force : Force the operation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Open Core Business Suite with demo data (sample users and data)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Open Core Business Suite Demo Setup...');
        $this->warn('⚠️  This will install DEMO data with sample users');
        $this->newLine();

        try {
            // Step 1: Run core migrations
            $this->runCoreMigrations();

            // Step 2: Run priority modules in dependency order
            $this->runPriorityModules();

            // Step 3: Run remaining modules
            $this->runRemainingModules();

            // Step 4: Run demo seeders
            $this->runDemoSeeders();

            $this->newLine();
            $this->info('✅ Demo setup completed successfully!');
            $this->newLine();
            $this->info('📋 Demo Login Credentials:');
            $this->line('   Email: admin@demo.com');
            $this->line('   Password: password123');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Setup failed: '.$e->getMessage());
            Log::error('Demo setup failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Run core migrations
     */
    protected function runCoreMigrations(): void
    {
        $this->info('📦 Running core migrations...');

        $options = [
            '--force' => true,
        ];

        if ($this->option('fresh')) {
            $this->warn('⚠️  Running migrate:fresh (all tables will be dropped)...');

            // Create database if it doesn't exist
            $this->createDatabaseIfNotExists();

            Artisan::call('migrate:fresh', $options);
        } else {
            Artisan::call('migrate', $options);
        }

        $this->line(Artisan::output());
        $this->info('✓ Core migrations completed');
        $this->newLine();
    }

    /**
     * Create database if it doesn't exist
     */
    protected function createDatabaseIfNotExists(): void
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        try {
            // Connect to MySQL without specifying database
            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("✓ Database '{$database}' ready");
        } catch (\PDOException $e) {
            $this->error("Failed to create database: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Run priority modules in correct dependency order
     */
    protected function runPriorityModules(): void
    {
        $this->info('📦 Running priority modules...');

        // Define priority modules based on dependencies
        $priorityModules = [
            'SystemCore',        // Core system functionality
            'FieldManager',      // Creates clients table (required by many modules)
            'SiteAttendance',    // Creates sites table (required by geofence, ip attendance)
            'BreakSystem',       // Creates attendance_breaks table (required by tracking)
            'Payroll',           // Creates payroll_records table (required by expenses)
            'FaceAttendance',    // Creates face_data table (required by FaceAttendanceDevice)
            'GeofenceSystem',    // Creates geofence_groups (uses sites table)
            'AccountingCore',    // Accounting functionality
            'WMSInventoryCore',  // Inventory/warehouse
        ];

        $modules = $this->getActiveModules();

        foreach ($priorityModules as $module) {
            if (in_array($module, $modules)) {
                $this->runModuleMigration($module, true);
            }
        }

        $this->newLine();
    }

    /**
     * Run remaining modules
     */
    protected function runRemainingModules(): void
    {
        $this->info('📦 Running remaining modules...');

        $priorityModules = [
            'SystemCore',
            'FieldManager',
            'SiteAttendance',
            'BreakSystem',
            'Payroll',
            'FaceAttendance',
            'GeofenceSystem',
            'AccountingCore',
            'WMSInventoryCore',
        ];

        // Get excluded modules (MultiTenancyCore included for demo - essential seeders run always, demo seeder is env-guarded)
        $excludedModules = [
            'PayPalGateway',
            'StripeGateway',
            'RazorpayGateway',
            'LandingPage',
        ];

        $modules = array_diff($this->getActiveModules(), $priorityModules, $excludedModules);

        foreach ($modules as $module) {
            $this->runModuleMigration($module, false);
        }

        $this->newLine();
    }

    /**
     * Run migration for a specific module
     */
    protected function runModuleMigration(string $module, bool $isPriority = false): void
    {
        try {
            $label = $isPriority ? '⭐ Priority' : '📄 Module';
            $this->line("  {$label}: {$module}");

            Artisan::call('module:migrate', [
                'module' => [$module],
                '--force' => true,
            ]);

            $this->info("    ✓ {$module} migrated successfully");
        } catch (\Exception $e) {
            $this->warn("    ⚠ {$module} migration failed: ".$e->getMessage());
            Log::warning("Failed to migrate module {$module}: ".$e->getMessage());
        }
    }

    /**
     * Get active modules
     */
    protected function getActiveModules(): array
    {
        $modulesStatusPath = base_path('modules_statuses.json');

        if (! file_exists($modulesStatusPath)) {
            return [];
        }

        $statuses = json_decode(file_get_contents($modulesStatusPath), true);

        return array_keys(array_filter($statuses, function ($status) {
            return $status === true;
        }));
    }

    /**
     * Run demo seeders
     */
    protected function runDemoSeeders(): void
    {
        $this->newLine();
        $this->info('🌱 Running demo seeders...');

        try {
            // Run DemoSeeder
            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoSeeder',
                '--force' => true,
            ]);

            $this->info('✓ DemoSeeder completed');

            // Run module seeders
            $this->runModuleSeeders();

            $this->info('✓ All seeders completed');
        } catch (\Exception $e) {
            $this->error('Failed to run seeders: '.$e->getMessage());
            Log::error('Demo seeder failed: '.$e->getMessage());
        }
    }

    /**
     * Run module seeders
     */
    protected function runModuleSeeders(): void
    {
        // MultiTenancyCore included - essential seeders run always, demo seeder is env-guarded
        $excludedModules = [
            'PayPalGateway',
            'StripeGateway',
            'RazorpayGateway',
            'LandingPage',
        ];

        $modules = array_diff($this->getActiveModules(), $excludedModules);

        foreach ($modules as $module) {
            try {
                // Check both casing variants (Database/Seeders and database/seeders)
                $seederPath = base_path("Modules/{$module}/Database/Seeders");
                $seederPathLower = base_path("Modules/{$module}/database/seeders");

                if (is_dir($seederPath) || is_dir($seederPathLower)) {
                    $this->line("  🌱 Seeding: {$module}");
                    Artisan::call('module:seed', [
                        'module' => $module,
                        '--force' => true,
                    ]);
                    $this->info("    ✓ {$module} seeded");
                }
            } catch (\Exception $e) {
                $this->warn("    ⚠ {$module} seeding failed: ".$e->getMessage());
                Log::warning("Failed to seed module {$module}: ".$e->getMessage());
            }
        }
    }
}
