<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SetupOpenCoreBSLiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opencorebs:live
                            {--fresh : Drop all tables and re-run migrations}
                            {--force : Force the operation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Open Core Business Suite for production (with interactive prompts)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Open Core Business Suite Production Setup...');
        $this->warn('⚠️  This will install PRODUCTION data');
        $this->newLine();

        try {
            // Step 1: Run core migrations
            $this->runCoreMigrations();

            // Step 2: Run priority modules in dependency order
            $this->runPriorityModules();

            // Step 3: Run remaining modules
            $this->runRemainingModules();

            // Step 4: Prompt for admin details and run live seeders
            $this->runLiveSeeders();

            $this->newLine();
            $this->info('✅ Production setup completed successfully!');
            $this->newLine();
            $this->info('🔐 Please update your company settings in the admin panel');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Setup failed: '.$e->getMessage());
            Log::error('Live setup failed: '.$e->getMessage(), [
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
            'GeofenceSystem',
            'AccountingCore',
            'WMSInventoryCore',
        ];

        // Get excluded modules (MultiTenancyCore included - essential seeders run, demo seeder is env-guarded)
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
     * Run live seeders with admin prompts
     */
    protected function runLiveSeeders(): void
    {
        $this->newLine();
        $this->info('🌱 Running production seeders...');
        $this->newLine();

        // Prompt for admin details
        $adminDetails = $this->promptForAdminDetails();

        try {
            // Set environment variables for LiveSeeder
            putenv("TENANT_ADMIN_EMAIL={$adminDetails['email']}");
            putenv("TENANT_ADMIN_PASSWORD={$adminDetails['password']}");
            putenv("TENANT_ADMIN_FIRST_NAME={$adminDetails['first_name']}");
            putenv("TENANT_ADMIN_LAST_NAME={$adminDetails['last_name']}");

            // Run LiveSeeder
            $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\LiveSeeder',
                '--force' => true,
            ]);

            $this->info('✓ LiveSeeder completed');

            // Run module seeders
            $this->runModuleSeeders();

            $this->info('✓ All seeders completed');

            // Display login credentials
            $this->newLine();
            $this->info('📋 Admin Login Credentials:');
            $this->line("   Email: {$adminDetails['email']}");
            $this->line("   Password: {$adminDetails['password']}");
            $this->newLine();
            $this->warn('⚠️  Please save these credentials securely!');
        } catch (\Exception $e) {
            $this->error('Failed to run seeders: '.$e->getMessage());
            Log::error('Live seeder failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Prompt for admin details
     */
    protected function promptForAdminDetails(): array
    {
        $this->info('🔧 Admin User Setup');
        $this->line('Please provide details for the administrator account:');
        $this->newLine();

        // Email with validation
        $email = $this->askWithValidation(
            'Admin Email',
            'admin@company.com',
            ['required', 'email']
        );

        // First name with validation
        $firstName = $this->askWithValidation(
            'Admin First Name',
            'Admin',
            ['required', 'string', 'min:2']
        );

        // Last name with validation
        $lastName = $this->askWithValidation(
            'Admin Last Name',
            'User',
            ['required', 'string', 'min:2']
        );

        // Password with validation and confirmation
        $password = $this->askPasswordWithConfirmation();

        $this->newLine();
        $this->info('✓ Admin details configured');
        $this->newLine();

        return [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $password,
        ];
    }

    /**
     * Ask a question with validation
     */
    protected function askWithValidation(string $question, string $default, array $rules): string
    {
        do {
            $answer = $this->ask($question, $default);

            $validator = Validator::make(
                [$question => $answer],
                [$question => $rules]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());
                $answer = null;
            }
        } while ($answer === null);

        return $answer;
    }

    /**
     * Ask for password with confirmation
     */
    protected function askPasswordWithConfirmation(): string
    {
        do {
            $password = $this->secret('Admin Password (min 8 characters)');

            // Validate password
            $validator = Validator::make(
                ['password' => $password],
                ['password' => ['required', 'string', 'min:8']]
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());

                continue;
            }

            // Confirm password
            $confirmation = $this->secret('Confirm Password');

            if ($password !== $confirmation) {
                $this->error('Passwords do not match. Please try again.');

                continue;
            }

            return $password;
        } while (true);
    }

    /**
     * Run module seeders
     */
    protected function runModuleSeeders(): void
    {
        // MultiTenancyCore included - essential seeders run, demo seeder is env-guarded
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
