<?php

namespace Modules\MultiTenancyCore\App\Console;

use Illuminate\Console\Command;
use Modules\MultiTenancyCore\App\Models\Tenant;

class TenantStorageLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:storage-link {--tenant= : Specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symbolic links for tenant storage directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return 1;
        }

        $this->info("Creating storage links for {$tenants->count()} tenant(s)...\n");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            $target = storage_path('app/tenants/'.$tenant->id);
            $link = public_path('storage/tenants/'.$tenant->id);

            // Ensure target directory exists
            if (! is_dir($target)) {
                mkdir($target, 0755, true);
            }

            // Ensure parent directory for link exists
            $linkParent = dirname($link);
            if (! is_dir($linkParent)) {
                mkdir($linkParent, 0755, true);
            }

            // Create symbolic link
            if (file_exists($link)) {
                $this->warn("  ⚠ Link already exists for tenant: {$tenant->name} ({$tenant->subdomain})");
                $skipped++;

                continue;
            }

            try {
                symlink($target, $link);
                $this->info("  ✓ Created link for tenant: {$tenant->name} ({$tenant->subdomain})");
                $created++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to create link for tenant: {$tenant->name}");
                $this->error("    Error: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Summary:');
        $this->line("  ✓ Created: {$created}");

        if ($skipped > 0) {
            $this->line("  ⚠ Skipped: {$skipped}");
        }

        if ($errors > 0) {
            $this->line("  ✗ Errors: {$errors}");
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $errors > 0 ? 1 : 0;
    }
}
