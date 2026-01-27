<?php

namespace Modules\MultiTenancyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MultiTenancyCore\App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: Core modules (AccountingCore, MultiTenancyCore) are ALWAYS accessible
     * in every plan and do not need to be explicitly listed in the modules array.
     * Only addon modules need to be specified here.
     */
    public function run(): void
    {
        $plans = [
            // STARTER PLAN - Basic features for small businesses
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses and startups',
                'price' => 499,
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'restrictions' => [
                    'max_users' => 10,
                    'max_employees' => 25,
                    'max_storage_gb' => 5,
                    // Core modules (AccountingCore, MultiTenancyCore) are automatically included
                    // Only list addon modules here - use exact module names from modules_statuses.json
                    'modules' => [
                        // Essential HR
                        'Payroll',

                        // Basic Operations
                        'Calendar',
                        'DocumentManagement',
                        'NoticeBoard',

                        // Task Management
                        'FieldTask',
                        'Notes',
                    ],
                ],
            ],

            // PROFESSIONAL PLAN - Advanced features for growing companies
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Great for growing companies with advanced needs',
                'price' => 1499,
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'restrictions' => [
                    'max_users' => 50,
                    'max_employees' => 100,
                    'max_storage_gb' => 25,
                    // Core modules are automatically included
                    // Use exact module names from modules_statuses.json
                    'modules' => [
                        // HR Extended
                        'Payroll',
                        'Recruitment',
                        'LoanManagement',
                        'Assets',

                        // Finance
                        'ExpenseManagement',

                        // Operations
                        'Calendar',
                        'DocumentManagement',
                        'NoticeBoard',
                        'FieldTask',
                        'Notes',
                        'Approvals',

                        // Communication
                        'ChatSystem',

                        // Sales
                        'SalesTarget',

                        // Attendance Systems
                        'IpAddressAttendance',
                        'QRAttendance',
                        'SiteAttendance',
                    ],
                ],
            ],

            // ENTERPRISE PLAN - All features for large organizations
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Everything you need for large organizations',
                'price' => 3999,
                'billing_period' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'restrictions' => [
                    'max_users' => -1, // Unlimited
                    'max_employees' => -1, // Unlimited
                    'max_storage_gb' => 100,
                    // Empty array = ALL MODULES ALLOWED (core + all addons)
                    'modules' => [],
                ],
            ],

            // UNLIMITED PLAN - No restrictions whatsoever
            [
                'name' => 'Unlimited',
                'slug' => 'unlimited',
                'description' => 'No limits, no restrictions - full access to everything',
                'price' => 7999,
                'billing_period' => 'yearly',
                'trial_days' => 30,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'restrictions' => [
                    'max_users' => -1, // Unlimited
                    'max_employees' => -1, // Unlimited
                    'max_storage_gb' => -1, // Unlimited
                    // Empty array = ALL MODULES ALLOWED (core + all addons)
                    'modules' => [],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
