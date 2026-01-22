<?php

namespace Modules\MultiTenancyCore\App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\TenantDatabase;

class TenantDatabaseService
{
    /**
     * Create database for tenant (VPS mode)
     */
    public function createDatabase(Tenant $tenant)
    {
        try {
            // Check if database already exists
            $existingDb = TenantDatabase::where('tenant_id', $tenant->id)->first();
            if ($existingDb) {
                // Drop the existing database and user
                try {
                    DB::statement("DROP DATABASE IF EXISTS `{$existingDb->database_name}`");
                    DB::statement("DROP USER IF EXISTS '{$existingDb->username}'@'localhost'");
                    DB::statement("DROP USER IF EXISTS '{$existingDb->username}'@'%'");
                } catch (\Exception $e) {
                    // Ignore errors
                }
                $existingDb->delete();
            }

            // Generate database credentials
            $dbName = 'tenant_'.$tenant->id.'_'.Str::slug($tenant->subdomain, '_');
            $dbUser = 'user_'.$tenant->id;
            $dbPassword = Str::random(16);

            // Drop and recreate database to ensure it's clean
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            DB::statement("CREATE DATABASE `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Create user and grant privileges
            $host = SaasSetting::get('general_tenant_db_host', 'localhost');

            // Drop existing users if they exist (in case of retry)
            try {
                DB::statement("DROP USER IF EXISTS '{$dbUser}'@'localhost'");
                DB::statement("DROP USER IF EXISTS '{$dbUser}'@'%'");
                DB::statement("DROP USER IF EXISTS '{$dbUser}'@'127.0.0.1'");
            } catch (\Exception $e) {
                // Ignore errors if users don't exist
            }

            // Create user and grant privileges
            DB::statement("CREATE USER '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");

            // Also create for % to ensure connectivity
            DB::statement("CREATE USER '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'");

            DB::statement('FLUSH PRIVILEGES');

            // Save database credentials
            TenantDatabase::create([
                'tenant_id' => $tenant->id,
                'host' => $host,
                'port' => SaasSetting::get('general_tenant_db_port', '3306'),
                'database_name' => $dbName,
                'username' => $dbUser,
                'encrypted_password' => Crypt::encryptString($dbPassword),
                'provisioning_status' => 'provisioned',
                'provisioned_at' => now(),
            ]);

            return [
                'success' => true,
                'database_name' => $dbName,
                'username' => $dbUser,
            ];

        } catch (\Exception $e) {
            Log::error('Database creation failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(TenantDatabase $tenantDatabase)
    {
        try {
            $password = Crypt::decryptString($tenantDatabase->encrypted_password);

            $connection = new \PDO(
                "mysql:host={$tenantDatabase->host};port={$tenantDatabase->port};dbname={$tenantDatabase->database_name}",
                $tenantDatabase->username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Test with a simple query
            $connection->query('SELECT 1');

            $tenantDatabase->last_verified_at = now();
            $tenantDatabase->save();

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tenant database connection config
     */
    public function getTenantConnectionConfig(Tenant $tenant)
    {
        $tenantDatabase = $tenant->database;

        if (! $tenantDatabase) {
            throw new \Exception('Tenant database not configured');
        }

        return [
            'driver' => 'mysql',
            'host' => $tenantDatabase->host,
            'port' => $tenantDatabase->port,
            'database' => $tenantDatabase->database_name,
            'username' => $tenantDatabase->username,
            'password' => Crypt::decryptString($tenantDatabase->encrypted_password),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }

    /**
     * Run migrations and seeders for tenant
     *
     * @param  bool  $seedDemo  If true, seeds demo/sample data (for local testing)
     */
    public function migrateAndSeed(Tenant $tenant, bool $seedDemo = false)
    {
        // Set up dynamic connection
        $connectionName = 'tenant_'.$tenant->id;
        config(['database.connections.'.$connectionName => $this->getTenantConnectionConfig($tenant)]);

        // Purge the database connection to ensure fresh connection
        DB::purge($connectionName);

        // Reconnect to ensure we're using the right database
        DB::reconnect($connectionName);

        // Set the default database connection to the tenant connection
        $originalConnection = config('database.default');
        config(['database.default' => $connectionName]);

        try {
            // Create a fresh migration table
            Artisan::call('migrate:install', [
                '--database' => $connectionName,
            ]);

            // Run only tenant-specific core migrations
            $this->runTenantCoreMigrations($connectionName);

            // Get modules configuration
            $excludedModules = config('multitenancycore.tenant_modules.excluded_modules', [
                'MultiTenancyCore',
                'PayPalGateway',
                'StripeGateway',
                'RazorpayGateway',
                'LandingPage',
            ]);

            $priorityModules = config('multitenancycore.tenant_modules.priority_modules', [
                'SystemCore',        // Core system functionality
                'FieldManager',     // Creates clients table (required by ProductOrder, FieldTask, Calendar, SiteAttendance, PaymentCollection)
                'SiteAttendance',   // Creates sites table (required by GeofenceSystem, IpAddressAttendance, attendance core)
                'BreakSystem',      // Creates attendance_breaks table (required by attendance tracking)
                'Payroll',          // Creates payroll_records table (required by expenses)
                'GeofenceSystem',   // Creates geofence_groups (uses sites table)
                'AccountingCore',   // Accounting functionality
                'WMSInventoryCore', // Inventory/warehouse
            ]);

            // Get active modules and filter out excluded ones
            $modules = array_diff($this->getActiveModules(), $excludedModules);

            Log::info("Active modules for tenant {$tenant->id}: ".implode(', ', $modules));
            Log::info('Priority modules: '.implode(', ', $priorityModules));

            // Run priority modules first
            foreach ($priorityModules as $module) {
                if (in_array($module, $modules)) {
                    Log::info("Running priority module: {$module}");
                    try {
                        Artisan::call('module:migrate', [
                            'module' => [$module],
                            '--database' => $connectionName,
                            '--force' => true,
                        ]);

                        Log::info("Successfully migrated priority module {$module} for tenant {$tenant->id}");
                    } catch (\Exception $e) {
                        Log::warning("Failed to migrate module {$module}: ".$e->getMessage());
                    }
                } else {
                    Log::info("Skipping priority module {$module} (not in active modules)");
                }
            }

            // Run remaining modules
            foreach ($modules as $module) {
                if (in_array($module, $priorityModules)) {
                    continue;
                }

                try {
                    // Use module:migrate command which is module-aware
                    Artisan::call('module:migrate', [
                        'module' => [$module],
                        '--database' => $connectionName,
                        '--force' => true,
                    ]);

                    // Log successful migration
                    Log::info("Successfully migrated module {$module} for tenant {$tenant->id}");
                } catch (\Exception $e) {
                    Log::warning("Failed to migrate module {$module}: ".$e->getMessage());
                }
            }

            // Now run seeders after all migrations are complete
            // Pass the original central connection name so seeders can fetch tenant owner data
            $this->runTenantSeeders($connectionName, $tenant, $originalConnection, $seedDemo);

        } finally {
            // Always restore the original connection
            config(['database.default' => $originalConnection]);
        }
    }

    /**
     * Get module migration paths
     */
    private function getModuleMigrationPaths()
    {
        $paths = [];
        $modulesPath = base_path('Modules');

        if (is_dir($modulesPath)) {
            $modules = scandir($modulesPath);

            foreach ($modules as $module) {
                // Skip MultiTenancyCore and non-directories
                if ($module === '.' || $module === '..' || $module === 'MultiTenancyCore' || ! is_dir($modulesPath.'/'.$module)) {
                    continue;
                }

                $migrationPath = "Modules/{$module}/database/migrations";
                if (is_dir(base_path($migrationPath))) {
                    $paths[] = $migrationPath;
                }
            }
        }

        return $paths;
    }

    /**
     * Run tenant seeders
     *
     * @param  string  $connectionName  The tenant database connection name
     * @param  Tenant  $tenant  The tenant model
     * @param  string  $centralConnection  The central database connection name (defaults to 'mysql')
     * @param  bool  $seedDemo  If true, seeds demo/sample data (for local testing)
     */
    private function runTenantSeeders(string $connectionName, Tenant $tenant, string $centralConnection = 'mysql', bool $seedDemo = false)
    {
        // Set the default connection for seeders
        config(['database.default' => $connectionName]);

        try {
            // Run LiveSeeder first - it handles all essential setup
            try {
                // Get tenant owner's password hash from central database
                // Use the explicit central connection to ensure we query the main database
                $tenantOwner = DB::connection($centralConnection)
                    ->table('users')
                    ->where('email', $tenant->email)
                    ->first();

                // Set admin credentials via environment variables for LiveSeeder
                putenv("TENANT_ADMIN_EMAIL={$tenant->email}");
                // Pass the actual password hash so tenant can login with their registration password
                if ($tenantOwner && $tenantOwner->password) {
                    putenv("TENANT_ADMIN_PASSWORD_HASH={$tenantOwner->password}");
                }
                putenv('TENANT_ADMIN_FIRST_NAME='.($tenant->name ? explode(' ', $tenant->name)[0] : 'Admin'));
                putenv('TENANT_ADMIN_LAST_NAME='.($tenant->name ? (explode(' ', $tenant->name)[1] ?? $tenant->name) : 'User'));

                Artisan::call('db:seed', [
                    '--database' => $connectionName,
                    '--class' => 'Database\\Seeders\\LiveSeeder',
                    '--force' => true,
                ]);

                // Clear environment variables
                putenv('TENANT_ADMIN_EMAIL');
                putenv('TENANT_ADMIN_PASSWORD_HASH');
                putenv('TENANT_ADMIN_FIRST_NAME');
                putenv('TENANT_ADMIN_LAST_NAME');

                Log::info("Successfully ran LiveSeeder for tenant {$tenant->id}");
            } catch (\Exception $e) {
                Log::error("Failed to run LiveSeeder for tenant {$tenant->id}: ".$e->getMessage());
                throw $e; // Re-throw to prevent partial setup
            }

            // Get additional tenant-specific seeders from config (optional)
            $additionalSeeders = config('multitenancycore.tenant_modules.additional_tenant_seeders', []);

            // Run each additional tenant seeder
            foreach ($additionalSeeders as $seederClass) {
                if (class_exists($seederClass)) {
                    try {
                        Artisan::call('db:seed', [
                            '--database' => $connectionName,
                            '--class' => $seederClass,
                            '--force' => true,
                        ]);
                        Log::info("Successfully ran seeder {$seederClass} for tenant {$tenant->id}");
                    } catch (\Exception $e) {
                        Log::warning("Failed to run seeder {$seederClass}: ".$e->getMessage());
                    }
                }
            }

            // Run only essential module seeders from config
            // This prevents demo/sample data from being seeded in production tenants
            $essentialModuleSeeders = config('multitenancycore.tenant_modules.essential_module_seeders', []);

            foreach ($essentialModuleSeeders as $seederClass) {
                if (class_exists($seederClass)) {
                    try {
                        Artisan::call('db:seed', [
                            '--database' => $connectionName,
                            '--class' => $seederClass,
                            '--force' => true,
                        ]);
                        Log::info("Successfully ran essential seeder {$seederClass} for tenant {$tenant->id}");
                    } catch (\Exception $e) {
                        Log::warning("Failed to run essential seeder {$seederClass}: ".$e->getMessage());
                    }
                } else {
                    Log::warning("Essential seeder class not found: {$seederClass}");
                }
            }

            // If demo mode is enabled, run ALL module seeders (for local testing)
            if ($seedDemo) {
                Log::info("Demo mode enabled - running all module seeders for tenant {$tenant->id}");

                $excludedModules = config('multitenancycore.tenant_modules.excluded_modules', [
                    'MultiTenancyCore',
                    'SubscriptionManagement',
                    'PayPalGateway',
                    'StripeGateway',
                    'RazorpayGateway',
                    'LandingPage',
                    'Billing',
                ]);

                $modules = array_diff($this->getActiveModules(), $excludedModules);

                foreach ($modules as $module) {
                    try {
                        // Check if module has seeders (both casing variants)
                        $seederPath = base_path("Modules/{$module}/Database/Seeders");
                        $seederPathLower = base_path("Modules/{$module}/database/seeders");

                        if (is_dir($seederPath) || is_dir($seederPathLower)) {
                            Artisan::call('module:seed', [
                                'module' => $module,
                                '--database' => $connectionName,
                                '--force' => true,
                            ]);
                            Log::info("Successfully seeded demo module {$module} for tenant {$tenant->id}");
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to seed demo module {$module}: ".$e->getMessage());
                    }
                }
            }

            // Note: Admin user is now created by LiveSeeder

        } finally {
            // Restore central connection as default
            config(['database.default' => $centralConnection]);
        }
    }

    /**
     * Get active modules
     */
    private function getActiveModules()
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
     * Create default admin user for tenant
     */
    private function createTenantAdmin(Tenant $tenant, $connectionName)
    {
        try {
            // Check if user already exists
            $existingUser = DB::connection($connectionName)->table('users')
                ->where('email', $tenant->email)
                ->first();

            if (! $existingUser) {
                // Get the tenant owner from main database
                $tenantOwner = DB::connection(config('database.default'))
                    ->table('users')
                    ->where('email', $tenant->email)
                    ->first();

                DB::connection($connectionName)->table('users')->insert([
                    'first_name' => $tenantOwner->first_name ?? 'Admin',
                    'last_name' => $tenantOwner->last_name ?? $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $tenantOwner->phone ?? $tenant->phone,
                    'password' => $tenantOwner->password ?? bcrypt('123456'), // Use same password as main account
                    'gender' => $tenantOwner->gender ?? 'male',
                    'code' => 'EMP-001', // First employee in tenant
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Get the inserted user ID
                $userId = DB::connection($connectionName)->getPdo()->lastInsertId();

                // Assign admin role
                $adminRoleId = DB::connection($connectionName)->table('roles')
                    ->where('name', 'admin')
                    ->value('id');

                if ($adminRoleId) {
                    DB::connection($connectionName)->table('model_has_roles')->insert([
                        'role_id' => $adminRoleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ]);
                }

                Log::info("Created admin user for tenant {$tenant->id}");
            }
        } catch (\Exception $e) {
            Log::error('Failed to create admin user for tenant: '.$e->getMessage());
        }
    }

    /**
     * Run tenant-specific core migrations
     */
    private function runTenantCoreMigrations($connectionName)
    {
        $excludedPatterns = config('multitenancycore.tenant_modules.excluded_core_migrations', [
            'telescope_entries',
            'failed_jobs',
            'jobs',
            'job_batches',
            'cache',
            'cache_locks',
            'sessions',
        ]);

        $migrationPath = database_path('migrations');
        $migrations = glob($migrationPath.'/*.php');

        foreach ($migrations as $migration) {
            $filename = basename($migration);

            // Check if this migration should be excluded
            $shouldExclude = false;
            foreach ($excludedPatterns as $pattern) {
                if (str_contains($filename, $pattern)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (! $shouldExclude) {
                try {
                    Artisan::call('migrate', [
                        '--database' => $connectionName,
                        '--path' => 'database/migrations/'.$filename,
                        '--force' => true,
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Failed to run migration {$filename}: ".$e->getMessage());
                }
            }
        }
    }
}
