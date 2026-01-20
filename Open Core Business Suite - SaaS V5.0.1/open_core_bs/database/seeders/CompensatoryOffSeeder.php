<?php

namespace Database\Seeders;

use App\Models\CompensatoryOff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompensatoryOffSeeder extends Seeder
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
            $this->command->warn('No users found for creating compensatory offs');

            return;
        }

        // Get a random approver (HR or Manager)
        $approver = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin', 'hr_manager', 'manager']);
        })->inRandomOrder()->first();

        if (! $approver) {
            $approver = $users->first();
        }

        // Create compensatory offs for each user
        foreach ($users as $user) {
            // Create 2-5 comp offs per user
            $compOffCount = rand(2, 5);

            for ($i = 0; $i < $compOffCount; $i++) {
                $this->createCompensatoryOff($user, $approver);
            }
        }

        $this->command->info('Compensatory offs seeded successfully!');
    }

    /**
     * Create a single compensatory off record
     */
    private function createCompensatoryOff(User $user, User $approver): void
    {
        // Random worked date in the past 6 months
        $workedDate = Carbon::now()->subMonths(rand(1, 6))->subDays(rand(0, 30));

        // Hours worked on weekend/holiday (typically 8-12 hours)
        $hoursWorked = [8, 8.5, 9, 10, 11, 12][array_rand([8, 8.5, 9, 10, 11, 12])];

        // Calculate comp off days (typically 1 day for 8+ hours)
        $compOffDays = $hoursWorked >= 8 ? 1 : 0.5;

        // Expiry date (3 months from worked date)
        $expiryDate = $workedDate->copy()->addMonths(3);

        // Determine status: 70% approved, 20% pending, 10% rejected
        $rand = rand(1, 100);
        if ($rand <= 70) {
            $status = 'approved';
            $approvedById = $approver->id;
            $approvedAt = $workedDate->copy()->addDays(rand(1, 7));
            $approvalNotes = $this->generateApprovalNotes(true);
        } elseif ($rand <= 90) {
            $status = 'pending';
            $approvedById = null;
            $approvedAt = null;
            $approvalNotes = null;
        } else {
            $status = 'rejected';
            $approvedById = $approver->id;
            $approvedAt = $workedDate->copy()->addDays(rand(1, 7));
            $approvalNotes = $this->generateApprovalNotes(false);
        }

        // Determine if comp off has been used (30% of approved ones)
        $isUsed = false;
        $usedDate = null;
        if ($status === 'approved' && rand(1, 100) <= 30 && $expiryDate > Carbon::now()) {
            $isUsed = true;
            // Used date is between approval and now
            $usedDate = $approvedAt->copy()->addDays(rand(7, 60));
            if ($usedDate > Carbon::now()) {
                $usedDate = Carbon::now()->subDays(rand(1, 30));
            }
        }

        CompensatoryOff::create([
            'user_id' => $user->id,
            'worked_date' => $workedDate->format('Y-m-d'),
            'hours_worked' => $hoursWorked,
            'comp_off_days' => $compOffDays,
            'reason' => $this->generateReason(),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'is_used' => $isUsed,
            'used_date' => $usedDate ? $usedDate->format('Y-m-d') : null,
            'status' => $status,
            'approved_by_id' => $approvedById,
            'approved_at' => $approvedAt,
            'approval_notes' => $approvalNotes,
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);
    }

    /**
     * Generate reason for working on weekend/holiday
     */
    private function generateReason(): string
    {
        $reasons = [
            'Project deadline - worked on weekend to complete delivery',
            'Production issue - emergency fix on Sunday',
            'Client demo preparation on Saturday',
            'System maintenance performed on public holiday',
            'Critical bug fix deployed on weekend',
            'Urgent client requirement - worked on holiday',
            'Server migration on weekend to minimize downtime',
            'Year-end closing activities on holiday',
            'Important project launch - weekend support',
            'Database backup and recovery on Sunday',
            'Security patch deployment on holiday',
            'Client presentation preparation on weekend',
        ];

        return $reasons[array_rand($reasons)];
    }

    /**
     * Generate approval notes
     */
    private function generateApprovalNotes(bool $approved): string
    {
        if ($approved) {
            $notes = [
                'Approved. Thank you for your dedication.',
                'Comp off granted. Valid for 3 months.',
                'Approved as requested. Please plan your leave accordingly.',
                'Granted. Appreciate your weekend effort.',
                'Approved. Use before expiry date.',
            ];
        } else {
            $notes = [
                'Insufficient documentation provided.',
                'Work was not pre-approved by manager.',
                'Regular work hours, not eligible for comp off.',
                'Please submit proper justification.',
                'Not applicable as per company policy.',
            ];
        }

        return $notes[array_rand($notes)];
    }
}
