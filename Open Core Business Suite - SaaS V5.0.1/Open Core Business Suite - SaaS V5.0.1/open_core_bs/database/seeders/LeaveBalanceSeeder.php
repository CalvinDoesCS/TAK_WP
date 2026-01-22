<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users except clients
        $users = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found for creating leave balances');

            return;
        }

        // Get all active leave types
        $leaveTypes = LeaveType::where('status', 'active')->get();

        if ($leaveTypes->isEmpty()) {
            $this->command->warn('No leave types found. Please run LeaveTypeSeeder first.');

            return;
        }

        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                // Create balance for last year
                $this->createLeaveBalance($user, $leaveType, $lastYear);

                // Create balance for current year
                $this->createLeaveBalance($user, $leaveType, $currentYear);
            }
        }

        $this->command->info('Leave balances seeded successfully!');
    }

    /**
     * Create leave balance for a user
     */
    private function createLeaveBalance(User $user, LeaveType $leaveType, int $year): void
    {
        // Determine entitled leaves based on leave type
        $entitledLeaves = $this->getEntitledLeaves($leaveType);

        // Random carried forward leaves (0-5 days from previous year, only for current year)
        $carriedForwardLeaves = 0;
        $carryForwardExpiryDate = null;

        if ($year === Carbon::now()->year && $leaveType->allow_carry_forward) {
            $carriedForwardLeaves = rand(0, 5);
            if ($carriedForwardLeaves > 0) {
                // Expiry date is typically 3-6 months into the year
                $carryForwardExpiryDate = Carbon::create($year, 1, 1)->addMonths(rand(3, 6));
            }
        }

        // Random additional leaves (adjustments: -2 to +5)
        $additionalLeaves = rand(-2, 5);

        // Calculate used leaves
        // For last year: 60-90% of entitled + additional
        // For current year: 20-60% of entitled + additional (less used so far)
        $totalAvailable = $entitledLeaves + $carriedForwardLeaves + $additionalLeaves;

        if ($year < Carbon::now()->year) {
            // Last year - most leaves used
            $usedLeaves = round($totalAvailable * (rand(60, 90) / 100), 1);
        } else {
            // Current year - fewer leaves used
            $usedLeaves = round($totalAvailable * (rand(20, 60) / 100), 1);
        }

        // Ensure used leaves doesn't exceed total available
        if ($usedLeaves > $totalAvailable) {
            $usedLeaves = $totalAvailable;
        }

        // Calculate available leaves
        $availableLeaves = $totalAvailable - $usedLeaves;

        // Create or update the balance record
        UserAvailableLeave::updateOrCreate(
            [
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'entitled_leaves' => $entitledLeaves,
                'carried_forward_leaves' => $carriedForwardLeaves,
                'additional_leaves' => $additionalLeaves,
                'used_leaves' => $usedLeaves,
                'available_leaves' => $availableLeaves,
                'carry_forward_expiry_date' => $carryForwardExpiryDate,
                'created_by_id' => $user->id,
                'updated_by_id' => $user->id,
            ]
        );
    }

    /**
     * Get entitled leaves based on leave type
     */
    private function getEntitledLeaves(LeaveType $leaveType): float
    {
        // Common leave entitlements based on type
        return match ($leaveType->code) {
            'annual_leave' => rand(15, 25),      // 15-25 days annual leave
            'sick_leave' => rand(10, 15),        // 10-15 days sick leave
            'casual_leave' => rand(5, 10),       // 5-10 days casual leave
            'emergency_leave' => rand(3, 7),     // 3-7 days emergency leave
            'maternity_leave' => rand(60, 90),   // 60-90 days maternity leave
            'paternity_leave' => rand(7, 14),    // 7-14 days paternity leave
            'study_leave' => rand(5, 10),        // 5-10 days study leave
            'bereavement_leave' => rand(3, 5),   // 3-5 days bereavement leave
            'unpaid_leave' => 0,                 // No entitled unpaid leave
            'work_from_home' => rand(20, 40),    // 20-40 days WFH
            default => rand(10, 15),             // Default for other types
        };
    }
}
