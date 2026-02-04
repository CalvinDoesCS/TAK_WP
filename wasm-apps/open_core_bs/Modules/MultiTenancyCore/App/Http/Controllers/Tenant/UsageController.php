<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\TenantManager;

class UsageController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Display the usage overview
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        $subscription = $tenant->activeSubscription;
        $plan = $subscription ? $subscription->plan : null;

        // Get plan limits using proper getter methods
        $maxUsers = $plan ? $plan->getMaxUsers() : null;
        $maxStorageGb = $plan ? $plan->getMaxStorageGb() : null;

        // Calculate storage usage in tenant context
        $storageUsage = $this->calculateStorageUsageInTenantContext($tenant);

        // Get usage statistics
        $usage = [
            'users' => [
                'current' => 1, // Can be expanded to count actual users
                'limit' => ($maxUsers === null || $maxUsers === -1) ? 'unlimited' : $maxUsers,
                'percentage' => ($maxUsers && $maxUsers > 0) ? (1 / $maxUsers) * 100 : 0,
            ],
            'storage' => [
                'current' => $storageUsage,
                'limit' => ($maxStorageGb === null || $maxStorageGb === -1) ? 'unlimited' : $maxStorageGb,
                'percentage' => 0,
            ],
            'api_calls' => [
                'current' => 0, // Placeholder for API usage tracking
                'limit' => 'unlimited',
                'percentage' => 0,
            ],
        ];

        // Calculate storage percentage if limit exists
        if ($maxStorageGb && $maxStorageGb > 0) {
            $usage['storage']['percentage'] = ($storageUsage / ($maxStorageGb * 1024 * 1024 * 1024)) * 100;
        }

        return view('multitenancycore::tenant.usage.index', compact(
            'tenant',
            'subscription',
            'plan',
            'usage'
        ));
    }

    /**
     * Calculate storage usage ensuring we're in tenant context
     */
    private function calculateStorageUsageInTenantContext(Tenant $tenant): int
    {
        // If in SaaS mode and tenant context is set, ensure we run in proper tenant context
        if (isSaaSMode() && $this->tenantManager->isTenantContext()) {
            return $this->tenantManager->forTenant($tenant, fn () => $this->calculateStorageUsage($tenant));
        }

        // Single-tenant mode or already in correct context - run directly
        return $this->calculateStorageUsage($tenant);
    }

    /**
     * Calculate storage usage for a tenant
     * Returns total storage used in bytes
     */
    private function calculateStorageUsage($tenant): int
    {
        $totalBytes = 0;

        // ═══════════════════════════════════════════════════════════
        // TABLES WITH file_size COLUMN (fast database sum)
        // ═══════════════════════════════════════════════════════════

        // Customer documents
        if (moduleExists('SystemCore')) {
            try {
                $totalBytes += (int) \Modules\SystemCore\App\Models\CustomerDocument::sum('file_size');
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Supplier documents
        if (moduleExists('SystemCore')) {
            try {
                $totalBytes += (int) \Modules\SystemCore\App\Models\SupplierDocument::sum('file_size');
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Asset documents
        if (moduleExists('Assets')) {
            try {
                $totalBytes += (int) \Modules\Assets\App\Models\AssetDocument::sum('file_size');
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Chat files (stored as string, cast to int)
        if (moduleExists('OCConnect')) {
            try {
                $totalBytes += (int) \Modules\OCConnect\App\Models\ChatFile::sum('file_size');
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // System backups
        if (moduleExists('SystemBackup')) {
            try {
                $totalBytes += (int) \Modules\SystemBackup\App\Models\SystemBackup::sum('file_size');
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // ═══════════════════════════════════════════════════════════
        // TABLES WITHOUT file_size COLUMN (filesystem calculation)
        // ═══════════════════════════════════════════════════════════

        // Employee documents
        if (moduleExists('DocumentManagement')) {
            try {
                $paths = \Modules\DocumentManagement\App\Models\EmployeeDocument::whereNotNull('file_path')
                    ->pluck('file_path');
                $totalBytes += $this->calculateSizeFromPaths($paths);
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // LMS lesson content files
        if (moduleExists('LMS')) {
            try {
                $paths = \Modules\LMS\App\Models\Lesson::whereNotNull('content_file_path')
                    ->pluck('content_file_path');
                $totalBytes += $this->calculateSizeFromPaths($paths);
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Employee onboarding checklist files
        if (moduleExists('Recruitment')) {
            try {
                $paths = \Modules\Recruitment\App\Models\EmployeeOnboardingChecklist::whereNotNull('uploaded_file_path')
                    ->pluck('uploaded_file_path');
                $totalBytes += $this->calculateSizeFromPaths($paths);
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Expense request documents (core app model, not a module)
        if (class_exists(\App\Models\ExpenseRequest::class)) {
            try {
                $paths = \App\Models\ExpenseRequest::whereNotNull('document_url')
                    ->pluck('document_url');
                $totalBytes += $this->calculateSizeFromPaths($paths);
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        // Document request generated files
        if (moduleExists('DocumentManagement')) {
            try {
                $paths = \Modules\DocumentManagement\App\Models\DocumentRequest::whereNotNull('generated_file')
                    ->pluck('generated_file');
                $totalBytes += $this->calculateSizeFromPaths($paths);
            } catch (\Exception $e) {
                // Table may not exist - skip silently
            }
        }

        return $totalBytes;
    }

    /**
     * Calculate total file size from an array of storage paths
     *
     * @param  \Illuminate\Support\Collection<int, string|null>  $paths
     */
    private function calculateSizeFromPaths($paths): int
    {
        $total = 0;
        // Use tenant disk if in tenant context, otherwise public
        $disk = function_exists('tenantDisk') ? tenantDisk() : 'public';

        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }

            try {
                // Try tenant disk first, fallback to public
                if (Storage::disk($disk)->exists($path)) {
                    $total += Storage::disk($disk)->size($path);
                } elseif ($disk !== 'public' && Storage::disk('public')->exists($path)) {
                    $total += Storage::disk('public')->size($path);
                }
            } catch (\Exception $e) {
                // Skip files that can't be accessed
                continue;
            }
        }

        return $total;
    }
}
