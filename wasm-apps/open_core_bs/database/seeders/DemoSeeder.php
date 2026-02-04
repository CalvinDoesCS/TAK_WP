<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ExpenseType;
use App\Models\Settings;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding demo data...');

        // Seed roles and permissions
        $this->call([
            RoleSeeder::class,
            HRCorePermissionSeeder::class,
        ]);

        // Seed base organizational data
        $this->seedTeamData();
        $this->seedShiftData();
        $this->seedExpenseTypesData();
        $this->seedDepartmentDesignationData();

        // Get required references
        $shift = Shift::where('is_default', true)->first();
        $team = Team::first();
        $adminDesignation = Designation::where('name', 'Admin Manager')->first();
        $hrDesignation = Designation::where('name', 'HR Manager')->first();
        $salesDesignation = Designation::where('name', 'Sales Representative')->first();
        $salesManagerDesignation = Designation::where('name', 'Sales Manager')->first();

        // Seed demo users
        $users = $this->seedDemoUsers($shift, $team, $adminDesignation, $hrDesignation, $salesDesignation, $salesManagerDesignation);

        // Seed employee lifecycle events for comprehensive demo data
        $this->seedEmployeeLifecycleEvents($users);

        // Seed leave and holiday data
        $this->call([
            LeaveTypeSeeder::class,
            LeaveBalanceSeeder::class,
            LeaveRequestSeeder::class,
            CompensatoryOffSeeder::class,
            HolidaySeeder::class,
        ]);

        // Seed application settings
        $this->seedSettings();

        $this->command->info('✅ Demo data seeded successfully!');
    }

    /**
     * Seed demo users with various roles and lifecycle scenarios
     */
    private function seedDemoUsers(
        Shift $shift,
        Team $team,
        Designation $adminDesignation,
        Designation $hrDesignation,
        Designation $salesDesignation,
        Designation $salesManagerDesignation
    ): array {
        $this->command->info('Creating demo users with lifecycle scenarios...');

        $allUsers = [];
        $salesTeam = Team::where('name', 'Sales Team 1')->first() ?? $team;
        $hrDepartment = Department::where('name', 'HR Department')->first();
        $itDepartment = Department::where('name', 'IT Department')->first();
        $marketingDepartment = Department::where('name', 'Marketing Department')->first();

        $hrManagerDes = Designation::where('name', 'HR Manager')->first();
        $itManagerDes = Designation::where('name', 'IT Manager')->first();
        $marketingManagerDes = Designation::where('name', 'Marketing Manager')->first();

        // Admin user
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@demo.com',
            'phone' => '1234567890',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'DEMO-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $team->id,
            'designation_id' => $adminDesignation->id,
            'date_of_joining' => now()->subYears(5),
        ]);
        $admin->assignRole('admin');
        $allUsers['admin'] = $admin;

        // HR user
        $hrUser = User::factory()->create([
            'first_name' => 'HR',
            'last_name' => 'User',
            'email' => 'hr@demo.com',
            'phone' => '0987654321',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'DEMO-002',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $team->id,
            'reporting_to_id' => $admin->id,
            'designation_id' => $hrDesignation->id,
            'date_of_joining' => now()->subYears(3),
        ]);
        $hrUser->assignRole('hr');
        $allUsers['hr'] = $hrUser;

        // Manager user
        $managerUser = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'User',
            'email' => 'manager@demo.com',
            'phone' => '0988654321',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'DEMO-003',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $hrUser->id,
            'designation_id' => $salesManagerDesignation->id,
            'date_of_joining' => now()->subYears(2),
        ]);
        $managerUser->assignRole('manager');
        $allUsers['manager'] = $managerUser;

        // Scenario 1: Active confirmed employee (completed probation)
        $confirmedEmp = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Confirmed',
            'email' => 'employee@demo.com',
            'phone' => '1111111111',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-001',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(8),
            'probation_period_months' => 6,
            'probation_end_date' => now()->subMonths(2),
            'probation_confirmed_at' => now()->subMonths(2),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $confirmedEmp->assignRole('field_employee');
        $allUsers['confirmed'] = $confirmedEmp;

        // Scenario 2: Currently on probation (will end in 2 months)
        $onProbation = User::factory()->create([
            'first_name' => 'Sarah',
            'last_name' => 'OnProbation',
            'email' => 'sarah.probation@demo.com',
            'phone' => '2222222222',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-002',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(4),
            'probation_period_months' => 6,
            'probation_end_date' => now()->addMonths(2),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $onProbation->assignRole('field_employee');
        $allUsers['on_probation'] = $onProbation;

        // Scenario 3: Extended probation
        $extendedProbation = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Extended',
            'email' => 'michael.extended@demo.com',
            'phone' => '3333333333',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-003',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(7),
            'probation_period_months' => 6,
            'probation_end_date' => now()->addMonth(),
            'is_probation_extended' => true,
            'probation_remarks' => 'Extended by 2 months for performance improvement',
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $extendedProbation->assignRole('field_employee');
        $allUsers['extended_probation'] = $extendedProbation;

        // Scenario 4: Onboarding employee (recently joined)
        $onboarding = User::factory()->create([
            'first_name' => 'Emma',
            'last_name' => 'Onboarding',
            'email' => 'emma.onboarding@demo.com',
            'phone' => '4444444444',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-004',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subDays(5),
            'probation_period_months' => 6,
            'probation_end_date' => now()->addMonths(6)->subDays(5),
            'status' => \App\Enums\UserAccountStatus::ONBOARDING,
        ]);
        $onboarding->assignRole('field_employee');
        $allUsers['onboarding'] = $onboarding;

        // Scenario 5: Promoted employee (with salary change)
        $promoted = User::factory()->create([
            'first_name' => 'David',
            'last_name' => 'Promoted',
            'email' => 'david.promoted@demo.com',
            'phone' => '5555555555',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-005',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $hrUser->id,
            'designation_id' => $salesManagerDesignation->id,
            'date_of_joining' => now()->subYears(2),
            'probation_confirmed_at' => now()->subMonths(18),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $promoted->assignRole('manager');
        $allUsers['promoted'] = $promoted;

        // Scenario 6: Suspended employee
        $suspended = User::factory()->create([
            'first_name' => 'Lisa',
            'last_name' => 'Suspended',
            'email' => 'lisa.suspended@demo.com',
            'phone' => '6666666666',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-006',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(14),
            'probation_confirmed_at' => now()->subMonths(8),
            'status' => \App\Enums\UserAccountStatus::SUSPENDED,
        ]);
        $suspended->assignRole('field_employee');
        $allUsers['suspended'] = $suspended;

        // Scenario 7: Terminated employee
        $terminated = User::factory()->create([
            'first_name' => 'Mark',
            'last_name' => 'Terminated',
            'email' => 'mark.terminated@demo.com',
            'phone' => '7777777777',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-007',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(10),
            'probation_confirmed_at' => now()->subMonths(4),
            'exit_date' => now()->subWeek(),
            'last_working_day' => now()->subWeek(),
            'exit_reason' => 'Performance issues - multiple warnings',
            'termination_type' => 'termination',
            'is_eligible_for_rehire' => false,
            'status' => \App\Enums\UserAccountStatus::TERMINATED,
        ]);
        $terminated->assignRole('field_employee');
        $allUsers['terminated'] = $terminated;

        // Scenario 8: Resigned employee
        $resigned = User::factory()->create([
            'first_name' => 'Rachel',
            'last_name' => 'Resigned',
            'email' => 'rachel.resigned@demo.com',
            'phone' => '8888888888',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-008',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subYears(1)->subMonths(3),
            'probation_confirmed_at' => now()->subMonths(9),
            'exit_date' => now()->subDays(15),
            'last_working_day' => now()->subDays(15),
            'exit_reason' => 'Better opportunity - voluntary resignation',
            'termination_type' => 'resignation',
            'is_eligible_for_rehire' => true,
            'status' => \App\Enums\UserAccountStatus::RELIEVED,
        ]);
        $resigned->assignRole('field_employee');
        $allUsers['resigned'] = $resigned;

        // Scenario 9: Employee with team change history
        $teamChanged = User::factory()->create([
            'first_name' => 'Tom',
            'last_name' => 'TeamChange',
            'email' => 'tom.teamchange@demo.com',
            'phone' => '9999999999',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-009',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $hrDepartment ? Team::where('name', 'Demo Team')->first()->id : $team->id,
            'reporting_to_id' => $hrUser->id,
            'designation_id' => $hrManagerDes ? $hrManagerDes->id : $hrDesignation->id,
            'date_of_joining' => now()->subMonths(15),
            'probation_confirmed_at' => now()->subMonths(9),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $teamChanged->assignRole('manager');
        $allUsers['team_changed'] = $teamChanged;

        // Scenario 10: Recently hired (probation ending soon - within 30 days)
        $probationEnding = User::factory()->create([
            'first_name' => 'Nina',
            'last_name' => 'ProbationEnding',
            'email' => 'nina.ending@demo.com',
            'phone' => '1010101010',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-010',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(5)->subWeeks(2),
            'probation_period_months' => 6,
            'probation_end_date' => now()->addWeeks(2),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $probationEnding->assignRole('field_employee');
        $allUsers['probation_ending'] = $probationEnding;

        // Scenario 11: Inactive employee
        $inactive = User::factory()->create([
            'first_name' => 'Peter',
            'last_name' => 'Inactive',
            'email' => 'peter.inactive@demo.com',
            'phone' => '1212121212',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-011',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $salesTeam->id,
            'reporting_to_id' => $managerUser->id,
            'designation_id' => $salesDesignation->id,
            'date_of_joining' => now()->subMonths(11),
            'probation_confirmed_at' => now()->subMonths(5),
            'status' => \App\Enums\UserAccountStatus::INACTIVE,
        ]);
        $inactive->assignRole('field_employee');
        $allUsers['inactive'] = $inactive;

        // Scenario 12: Long-serving employee (5+ years)
        $longServing = User::factory()->create([
            'first_name' => 'Robert',
            'last_name' => 'Veteran',
            'email' => 'robert.veteran@demo.com',
            'phone' => '1313131313',
            'phone_verified_at' => now(),
            'password' => bcrypt('password123'),
            'code' => 'EMP-012',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'shift_id' => $shift->id,
            'team_id' => $itDepartment ? Team::where('name', 'Team 3')->first()->id : $team->id,
            'reporting_to_id' => $admin->id,
            'designation_id' => $itManagerDes ? $itManagerDes->id : $adminDesignation->id,
            'date_of_joining' => now()->subYears(7),
            'probation_confirmed_at' => now()->subYears(7)->addMonths(6),
            'status' => \App\Enums\UserAccountStatus::ACTIVE,
        ]);
        $longServing->assignRole('manager');
        $allUsers['long_serving'] = $longServing;

        $this->command->info('✓ Demo users created with various lifecycle scenarios');

        return $allUsers;
    }

    /**
     * Seed team data
     */
    private function seedTeamData(): void
    {
        $teams = [
            ['name' => 'Default Team', 'code' => 'TM-001', 'status' => Status::ACTIVE, 'is_chat_enabled' => true],
            ['name' => 'Sales Team 1', 'code' => 'TM-002', 'status' => Status::ACTIVE, 'is_chat_enabled' => true],
            ['name' => 'Demo Team', 'code' => 'TM-003', 'status' => Status::ACTIVE, 'is_chat_enabled' => true],
            ['name' => 'Team 3', 'code' => 'TM-004', 'status' => Status::ACTIVE, 'is_chat_enabled' => true],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }

        $this->command->info('✓ Teams created');
    }

    /**
     * Seed shift data
     */
    private function seedShiftData(): void
    {
        $shifts = [
            [
                'name' => 'Default Shift',
                'code' => 'SH-001',
                'status' => Status::ACTIVE,
                'start_date' => now(),
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_default' => true,
                'sunday' => false,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => false,
            ],
            [
                'name' => 'Evening Shift',
                'code' => 'SH-002',
                'status' => Status::ACTIVE,
                'start_date' => now(),
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'is_default' => false,
                'sunday' => false,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => false,
            ],
            [
                'name' => 'Night Shift',
                'code' => 'SH-003',
                'status' => Status::ACTIVE,
                'start_date' => now(),
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'is_default' => false,
                'sunday' => false,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => false,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::create($shift);
        }

        $this->command->info('✓ Shifts created');
    }

    /**
     * Seed expense types
     */
    private function seedExpenseTypesData(): void
    {
        $expenseTypes = [
            ['name' => 'Travel', 'code' => 'TRAVEL', 'notes' => 'Travel Expense', 'is_proof_required' => false],
            ['name' => 'Food', 'code' => 'FOOD', 'notes' => 'Food Expense', 'is_proof_required' => false],
            ['name' => 'Accommodation', 'code' => 'ACCOMMODATION', 'notes' => 'Accommodation Expense', 'is_proof_required' => false],
            ['name' => 'Miscellaneous', 'code' => 'MISC', 'notes' => 'Miscellaneous Expense', 'is_proof_required' => false],
        ];

        foreach ($expenseTypes as $expenseType) {
            ExpenseType::create($expenseType);
        }

        $this->command->info('✓ Expense types created');
    }

    /**
     * Seed departments and designations
     */
    private function seedDepartmentDesignationData(): void
    {
        $department = Department::create([
            'name' => 'Default Department',
            'code' => 'DEPT-001',
            'notes' => 'Default Department',
        ]);

        Designation::create([
            'name' => 'Default Designation',
            'code' => 'DES-001',
            'department_id' => $department->id,
            'notes' => 'Default Designation',
        ]);

        $salesDepartment = Department::create([
            'name' => 'Sales Department',
            'code' => 'DEPT-002',
            'notes' => 'Sales Department',
        ]);

        Designation::create([
            'name' => 'Sales Manager',
            'code' => 'DES-002',
            'department_id' => $salesDepartment->id,
            'notes' => 'Sales Manager',
        ]);

        Designation::create([
            'name' => 'Sales Executive',
            'code' => 'DES-003',
            'department_id' => $salesDepartment->id,
            'notes' => 'Sales Executive',
        ]);

        Designation::create([
            'name' => 'Sales Associate',
            'code' => 'DES-004',
            'department_id' => $salesDepartment->id,
            'notes' => 'Sales Associate',
        ]);

        Designation::create([
            'name' => 'Sales Representative',
            'code' => 'DES-005',
            'department_id' => $salesDepartment->id,
            'notes' => 'Sales Representative',
        ]);

        $hrDepartment = Department::create([
            'name' => 'HR Department',
            'code' => 'DEPT-003',
            'notes' => 'HR Department',
        ]);

        Designation::create([
            'name' => 'HR Manager',
            'code' => 'DES-006',
            'department_id' => $hrDepartment->id,
            'notes' => 'HR Manager',
        ]);

        Designation::create([
            'name' => 'HR Executive',
            'code' => 'DES-007',
            'department_id' => $hrDepartment->id,
            'notes' => 'HR Executive',
        ]);

        Designation::create([
            'name' => 'HR Associate',
            'code' => 'DES-008',
            'department_id' => $hrDepartment->id,
            'notes' => 'HR Associate',
        ]);

        $itDepartment = Department::create([
            'name' => 'IT Department',
            'code' => 'DEPT-004',
            'notes' => 'IT Department',
        ]);

        Designation::create([
            'name' => 'IT Manager',
            'code' => 'DES-009',
            'department_id' => $itDepartment->id,
            'notes' => 'IT Manager',
        ]);

        Designation::create([
            'name' => 'IT Executive',
            'code' => 'DES-010',
            'department_id' => $itDepartment->id,
            'notes' => 'IT Executive',
        ]);

        Designation::create([
            'name' => 'IT Associate',
            'code' => 'DES-011',
            'department_id' => $itDepartment->id,
            'notes' => 'IT Associate',
        ]);

        $financeDepartment = Department::create([
            'name' => 'Finance Department',
            'code' => 'DEPT-005',
            'notes' => 'Finance Department',
        ]);

        Designation::create([
            'name' => 'Finance Manager',
            'code' => 'DES-012',
            'department_id' => $financeDepartment->id,
            'notes' => 'Finance Manager',
        ]);

        Designation::create([
            'name' => 'Finance Executive',
            'code' => 'DES-013',
            'department_id' => $financeDepartment->id,
            'notes' => 'Finance Executive',
        ]);

        Designation::create([
            'name' => 'Finance Associate',
            'code' => 'DES-014',
            'department_id' => $financeDepartment->id,
            'notes' => 'Finance Associate',
        ]);

        $marketingDepartment = Department::create([
            'name' => 'Marketing Department',
            'code' => 'DEPT-006',
            'notes' => 'Marketing Department',
        ]);

        Designation::create([
            'name' => 'Marketing Manager',
            'code' => 'DES-015',
            'department_id' => $marketingDepartment->id,
            'notes' => 'Marketing Manager',
        ]);

        Designation::create([
            'name' => 'Marketing Executive',
            'code' => 'DES-016',
            'department_id' => $marketingDepartment->id,
            'notes' => 'Marketing Executive',
        ]);

        Designation::create([
            'name' => 'Marketing Associate',
            'code' => 'DES-017',
            'department_id' => $marketingDepartment->id,
            'notes' => 'Marketing Associate',
        ]);

        $operationsDepartment = Department::create([
            'name' => 'Operations Department',
            'code' => 'DEPT-007',
            'notes' => 'Operations Department',
        ]);

        Designation::create([
            'name' => 'Operations Manager',
            'code' => 'DES-018',
            'department_id' => $operationsDepartment->id,
            'notes' => 'Operations Manager',
        ]);

        Designation::create([
            'name' => 'Operations Executive',
            'code' => 'DES-019',
            'department_id' => $operationsDepartment->id,
            'notes' => 'Operations Executive',
        ]);

        Designation::create([
            'name' => 'Operations Associate',
            'code' => 'DES-020',
            'department_id' => $operationsDepartment->id,
            'notes' => 'Operations Associate',
        ]);

        $adminDepartment = Department::create([
            'name' => 'Admin Department',
            'code' => 'DEPT-008',
            'notes' => 'Admin Department',
        ]);

        Designation::create([
            'name' => 'Admin Manager',
            'code' => 'DES-021',
            'department_id' => $adminDepartment->id,
            'notes' => 'Admin Manager',
        ]);

        Designation::create([
            'name' => 'Admin Executive',
            'code' => 'DES-022',
            'department_id' => $adminDepartment->id,
            'notes' => 'Admin Executive',
        ]);

        Designation::create([
            'name' => 'Admin Associate',
            'code' => 'DES-023',
            'department_id' => $adminDepartment->id,
            'notes' => 'Admin Associate',
        ]);

        $this->command->info('✓ Departments and designations created');
    }

    /**
     * Seed application settings
     */
    private function seedSettings(): void
    {
        Settings::create([
            'website' => 'https://czappstudio.com',
            'support_email' => 'support@czappstudio.com',
            'support_phone' => '+91 88254 39260',
            'support_whatsapp' => '+91 88254 39260',
            'company_name' => 'CZ App Studio',
            'company_logo' => 'app_logo.png',
            'company_address' => '2nd floor, 48/111, 2nd Ave, near Nagathamman temple, Thanikachalam Nagar, F Block, Ponniammanmedu, Chennai, Tamil Nadu 600110',
            'company_phone' => '+91-8825439260',
            'company_email' => 'support@czappstudio.com',
            'company_website' => 'https://czappstudio.com',
            'company_country' => 'India',
            'company_state' => 'Tamil Nadu',
            'company_city' => 'Chennai',
            'company_zipcode' => '600110',
            'company_tax_id' => 'GSTIN1234567890',
            'company_reg_no' => 'REG1234567890',
        ]);

        $this->command->info('✓ Settings created');
    }

    /**
     * Seed employee lifecycle events for demo users
     */
    private function seedEmployeeLifecycleEvents(array $users): void
    {
        $this->command->info('Creating lifecycle events...');

        // Scenario 1: Confirmed employee - Joined → Probation Started → Probation Confirmed
        if (isset($users['confirmed'])) {
            $user = $users['confirmed'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: [
                    'probation_end_date' => $user->probation_end_date->format('Y-m-d'),
                    'confirmed_at' => $user->probation_confirmed_at->format('Y-m-d'),
                ],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
        }

        // Scenario 2: On probation - Joined → Currently on probation
        if (isset($users['on_probation'])) {
            $user = $users['on_probation'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
        }

        // Scenario 3: Extended probation - Joined → Extended
        if (isset($users['extended_probation'])) {
            $user = $users['extended_probation'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_EXTENDED,
                metadata: [
                    'extension_months' => 2,
                    'new_end_date' => $user->probation_end_date->format('Y-m-d'),
                    'reason' => $user->probation_remarks,
                ],
                notes: 'Probation period extended by 2 months for performance improvement',
                eventDate: now()->subMonth()
            );
        }

        // Scenario 4: Onboarding - Just joined
        if (isset($users['onboarding'])) {
            $user = $users['onboarding'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::ONBOARDING_STARTED,
                metadata: ['onboarding_start_date' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee onboarding process started',
                eventDate: $user->date_of_joining
            );
        }

        // Scenario 5: Promoted employee - Multiple events
        if (isset($users['promoted'])) {
            $user = $users['promoted'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROMOTED,
                oldValue: ['designation' => 'Sales Representative'],
                newValue: ['designation' => 'Sales Manager'],
                metadata: ['promotion_date' => now()->subMonths(2)->format('Y-m-d')],
                notes: 'Promoted from Sales Representative to Sales Manager for outstanding performance',
                eventDate: now()->subMonths(2)
            );
        }

        // Scenario 6: Suspended employee
        if (isset($users['suspended'])) {
            $user = $users['suspended'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::WARNING_ISSUED,
                metadata: ['warning_date' => now()->subMonths(2)->format('Y-m-d'), 'reason' => 'Attendance issues'],
                notes: 'First warning issued for poor attendance',
                eventDate: now()->subMonths(2)
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::WARNING_ISSUED,
                metadata: ['warning_date' => now()->subMonth()->format('Y-m-d'), 'reason' => 'Continued attendance issues'],
                notes: 'Second warning issued for continued attendance problems',
                eventDate: now()->subMonth()
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::SUSPENDED,
                metadata: ['suspension_date' => now()->subWeeks(2)->format('Y-m-d'), 'duration' => '30 days'],
                notes: 'Suspended for 30 days due to repeated violations',
                eventDate: now()->subWeeks(2)
            );
        }

        // Scenario 7: Terminated employee
        if (isset($users['terminated'])) {
            $user = $users['terminated'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::TERMINATION_INITIATED,
                metadata: [
                    'initiation_date' => now()->subWeeks(2)->format('Y-m-d'),
                    'reason' => 'Performance issues',
                ],
                notes: 'Termination process initiated due to performance issues',
                eventDate: now()->subWeeks(2)
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::TERMINATED,
                metadata: [
                    'termination_type' => $user->termination_type,
                    'exit_reason' => $user->exit_reason,
                    'exit_date' => $user->exit_date->format('Y-m-d'),
                    'last_working_day' => $user->last_working_day->format('Y-m-d'),
                    'is_eligible_for_rehire' => $user->is_eligible_for_rehire,
                ],
                notes: 'Employee terminated - '.$user->exit_reason,
                eventDate: $user->exit_date
            );
        }

        // Scenario 8: Resigned employee
        if (isset($users['resigned'])) {
            $user = $users['resigned'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::RESIGNATION_SUBMITTED,
                metadata: ['submission_date' => now()->subDays(45)->format('Y-m-d')],
                notes: 'Employee submitted resignation - 30 days notice period',
                eventDate: now()->subDays(45)
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::RESIGNATION_ACCEPTED,
                metadata: ['acceptance_date' => now()->subDays(30)->format('Y-m-d')],
                notes: 'Resignation accepted by management',
                eventDate: now()->subDays(30)
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::RELIEVED,
                metadata: [
                    'termination_type' => $user->termination_type,
                    'exit_reason' => $user->exit_reason,
                    'exit_date' => $user->exit_date->format('Y-m-d'),
                    'is_eligible_for_rehire' => $user->is_eligible_for_rehire,
                ],
                notes: 'Employee relieved on good terms - '.$user->exit_reason,
                eventDate: $user->exit_date
            );
        }

        // Scenario 9: Team changed employee
        if (isset($users['team_changed'])) {
            $user = $users['team_changed'];
            $salesTeam = Team::where('name', 'Sales Team 1')->first();
            $demoTeam = Team::where('name', 'Demo Team')->first();

            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::TEAM_CHANGED,
                oldValue: ['team_id' => $salesTeam->id ?? 0],
                newValue: ['team_id' => $demoTeam->id ?? 0],
                metadata: [
                    'old_team' => $salesTeam->name ?? 'Sales Team',
                    'new_team' => $demoTeam->name ?? 'Demo Team',
                ],
                notes: 'Team changed from Sales Team to Demo Team for organizational restructuring',
                eventDate: now()->subMonths(3)
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::DESIGNATION_CHANGED,
                oldValue: ['designation' => 'Sales Representative'],
                newValue: ['designation' => 'HR Manager'],
                metadata: [
                    'old_designation' => 'Sales Representative',
                    'new_designation' => 'HR Manager',
                ],
                notes: 'Designation changed from Sales Representative to HR Manager',
                eventDate: now()->subMonths(3)
            );
        }

        // Scenario 10: Probation ending soon
        if (isset($users['probation_ending'])) {
            $user = $users['probation_ending'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
        }

        // Scenario 11: Inactive employee
        if (isset($users['inactive'])) {
            $user = $users['inactive'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::DEACTIVATED,
                metadata: ['deactivation_date' => now()->subWeeks(3)->format('Y-m-d'), 'reason' => 'Extended medical leave'],
                notes: 'Employee deactivated temporarily due to extended medical leave',
                eventDate: now()->subWeeks(3)
            );
        }

        // Scenario 12: Long-serving employee with rich history
        if (isset($users['long_serving'])) {
            $user = $users['long_serving'];
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $user->date_of_joining->format('Y-m-d')],
                notes: 'Employee joined the organization',
                eventDate: $user->date_of_joining
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::PROBATION_CONFIRMED,
                metadata: ['confirmed_at' => $user->probation_confirmed_at->format('Y-m-d')],
                notes: 'Probation period successfully completed',
                eventDate: $user->probation_confirmed_at
            );

            // Bonus awards
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::BONUS_AWARDED,
                metadata: ['amount' => 5000, 'type' => 'Annual Performance Bonus'],
                notes: 'Annual performance bonus of $5000 awarded',
                eventDate: now()->subYear()
            );
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::BONUS_AWARDED,
                metadata: ['amount' => 6000, 'type' => 'Annual Performance Bonus'],
                notes: 'Annual performance bonus of $6000 awarded for outstanding performance',
                eventDate: now()->subMonths(2)
            );
        }

        // Admin, HR, Manager joining events
        if (isset($users['admin'])) {
            $users['admin']->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $users['admin']->date_of_joining->format('Y-m-d')],
                notes: 'Admin user joined the organization',
                eventDate: $users['admin']->date_of_joining
            );
        }
        if (isset($users['hr'])) {
            $users['hr']->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $users['hr']->date_of_joining->format('Y-m-d')],
                notes: 'HR user joined the organization',
                eventDate: $users['hr']->date_of_joining
            );
        }
        if (isset($users['manager'])) {
            $users['manager']->logLifecycleEvent(
                \App\Enums\LifecycleEventType::JOINED,
                metadata: ['date_of_joining' => $users['manager']->date_of_joining->format('Y-m-d')],
                notes: 'Manager user joined the organization',
                eventDate: $users['manager']->date_of_joining
            );
        }

        $this->command->info('✓ Lifecycle events created for all demo employees');
    }
}
