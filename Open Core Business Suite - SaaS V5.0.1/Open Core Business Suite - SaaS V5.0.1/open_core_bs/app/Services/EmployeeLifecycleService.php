<?php

namespace App\Services;

use App\Enums\UserAccountStatus;
use App\Models\EmployeeLifecycleEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class EmployeeLifecycleService
{
    /**
     * Get the lifecycle timeline for an employee.
     *
     * @param  User  $user  The employee user
     * @return array Timeline events sorted chronologically (newest first)
     */
    public function getTimeline(User $user): array
    {
        $events = [];

        // 1. Get events from employee_lifecycle_events table (primary source)
        $events = array_merge($events, $this->getLifecycleEventRecords($user));

        // 2. Get lifecycle date events from user model (fallback for legacy data)
        $events = array_merge($events, $this->getLifecycleDateEvents($user));

        // 3. Get audit events (creation, status changes, field changes) (fallback)
        $events = array_merge($events, $this->getAuditEvents($user));

        // 4. Remove duplicates based on type and date
        $events = $this->removeDuplicateEvents($events);

        // 5. Sort events chronologically (newest first)
        usort($events, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $events;
    }

    /**
     * Get lifecycle events from employee_lifecycle_events table.
     */
    protected function getLifecycleEventRecords(User $user): array
    {
        $lifecycleEvents = EmployeeLifecycleEvent::forUser($user->id)
            ->with('triggeredBy')
            ->orderBy('event_date', 'desc')
            ->get();

        return $lifecycleEvents->map(function ($event) {
            return $event->toTimelineFormat();
        })->toArray();
    }

    /**
     * Remove duplicate events (prefer lifecycle_events table over audits).
     */
    protected function removeDuplicateEvents(array $events): array
    {
        $unique = [];
        $seen = [];

        foreach ($events as $event) {
            // Create a unique key based on type and date (rounded to minute)
            $dateKey = substr($event['date'], 0, 16); // YYYY-MM-DD HH:MM
            $key = $event['type'].'_'.$dateKey;

            if (! isset($seen[$key])) {
                $unique[] = $event;
                $seen[$key] = true;
            }
        }

        return $unique;
    }

    /**
     * Get lifecycle date events from user model fields.
     */
    protected function getLifecycleDateEvents(User $user): array
    {
        $events = [];

        // Date of Joining
        if ($user->date_of_joining) {
            $dojDate = $this->parseDate($user->date_of_joining);
            $events[] = [
                'date' => $dojDate->format('Y-m-d H:i:s'),
                'type' => 'date_of_joining',
                'title' => __('Employee Joined'),
                'description' => __('Employee joined the organization'),
                'icon' => 'bx-user-plus',
                'color' => 'success',
                'metadata' => [
                    'date_of_joining' => $dojDate->format('d-m-Y'),
                ],
            ];
        }

        // Probation End Date
        if ($user->probation_end_date) {
            $probEndDate = $this->parseDate($user->probation_end_date);
            $events[] = [
                'date' => $probEndDate->format('Y-m-d H:i:s'),
                'type' => 'probation_scheduled_end',
                'title' => __('Probation End Date'),
                'description' => __('Scheduled probation end date'),
                'icon' => 'bx-calendar-check',
                'color' => 'info',
                'metadata' => [
                    'probation_end_date' => $probEndDate->format('d-m-Y'),
                    'is_extended' => $user->is_probation_extended ? 'Yes' : 'No',
                ],
            ];
        }

        // Probation Confirmed
        if ($user->probation_confirmed_at) {
            $probConfirmedDate = $this->parseDate($user->probation_confirmed_at);
            $events[] = [
                'date' => $probConfirmedDate->format('Y-m-d H:i:s'),
                'type' => 'probation_confirmed',
                'title' => __('Probation Confirmed'),
                'description' => __('Employee probation period successfully completed'),
                'icon' => 'bx-check-circle',
                'color' => 'success',
                'metadata' => [
                    'confirmed_at' => $probConfirmedDate->format('d-m-Y h:i A'),
                    'remarks' => $user->probation_remarks,
                ],
            ];
        }

        // Exit Date
        if ($user->exit_date) {
            $exitDate = $this->parseDate($user->exit_date);
            $events[] = [
                'date' => $exitDate->format('Y-m-d H:i:s'),
                'type' => 'exit_initiated',
                'title' => __('Exit Initiated'),
                'description' => __('Employee exit process initiated'),
                'icon' => 'bx-exit',
                'color' => 'warning',
                'metadata' => [
                    'exit_date' => $exitDate->format('d-m-Y'),
                    'exit_reason' => $user->exit_reason,
                    'termination_type' => $user->termination_type,
                    'eligible_for_rehire' => $user->is_eligible_for_rehire ? 'Yes' : 'No',
                ],
            ];
        }

        // Relieved
        if ($user->relieved_at) {
            $relievedDate = $this->parseDate($user->relieved_at);
            $events[] = [
                'date' => $relievedDate->format('Y-m-d H:i:s'),
                'type' => 'relieved',
                'title' => __('Employee Relieved'),
                'description' => __('Employee relieved from duties'),
                'icon' => 'bx-user-minus',
                'color' => 'danger',
                'metadata' => [
                    'relieved_at' => $relievedDate->format('d-m-Y h:i A'),
                    'relieved_reason' => $user->relieved_reason,
                ],
            ];
        }

        // Retired
        if ($user->retired_at) {
            $retiredDate = $this->parseDate($user->retired_at);
            $events[] = [
                'date' => $retiredDate->format('Y-m-d H:i:s'),
                'type' => 'retired',
                'title' => __('Employee Retired'),
                'description' => __('Employee retired from organization'),
                'icon' => 'bx-home-heart',
                'color' => 'primary',
                'metadata' => [
                    'retired_at' => $retiredDate->format('d-m-Y h:i A'),
                    'retired_reason' => $user->retired_reason,
                ],
            ];
        }

        return $events;
    }

    /**
     * Parse date to Carbon instance (handles both string and Carbon).
     */
    protected function parseDate($date): Carbon
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    /**
     * Get audit events (creation, updates, status changes).
     */
    protected function getAuditEvents(User $user): array
    {
        $events = [];

        // Get all audits for this user
        $audits = Audit::where('auditable_type', 'App\Models\User')
            ->where('auditable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($audits as $audit) {
            $event = $this->processAuditEvent($audit, $user);
            if ($event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Process individual audit event.
     */
    protected function processAuditEvent(Audit $audit, User $user): ?array
    {
        $oldValues = $audit->old_values ?? [];
        $newValues = $audit->new_values ?? [];

        // Handle created event
        if ($audit->event === 'created') {
            return [
                'date' => $audit->created_at->format('Y-m-d H:i:s'),
                'type' => 'created',
                'title' => __('Employee Record Created'),
                'description' => __('Employee record was created in the system'),
                'icon' => 'bx-user-check',
                'color' => 'success',
                'metadata' => [
                    'created_by' => $audit->user ? $audit->user->getFullName() : 'System',
                ],
            ];
        }

        // Handle status changes
        if (isset($oldValues['status']) && isset($newValues['status']) && $oldValues['status'] !== $newValues['status']) {
            return $this->getStatusChangeEvent($audit, $oldValues['status'], $newValues['status']);
        }

        // Handle team changes
        if (isset($oldValues['team_id']) && isset($newValues['team_id']) && $oldValues['team_id'] !== $newValues['team_id']) {
            return $this->getTeamChangeEvent($audit, $oldValues['team_id'], $newValues['team_id']);
        }

        // Handle designation changes
        if (isset($oldValues['designation_id']) && isset($newValues['designation_id']) && $oldValues['designation_id'] !== $newValues['designation_id']) {
            return $this->getDesignationChangeEvent($audit, $oldValues['designation_id'], $newValues['designation_id']);
        }

        //TODO: Handle salary changes

        // Handle reporting manager changes
        if (isset($oldValues['reporting_to_id']) && isset($newValues['reporting_to_id']) && $oldValues['reporting_to_id'] !== $newValues['reporting_to_id']) {
            return $this->getReportingManagerChangeEvent($audit, $oldValues['reporting_to_id'], $newValues['reporting_to_id']);
        }

        // Handle probation extension
        if (isset($oldValues['is_probation_extended']) && isset($newValues['is_probation_extended']) &&
            $oldValues['is_probation_extended'] !== $newValues['is_probation_extended'] &&
            $newValues['is_probation_extended'] == true) {
            return [
                'date' => $audit->created_at->format('Y-m-d H:i:s'),
                'type' => 'probation_extended',
                'title' => __('Probation Extended'),
                'description' => __('Employee probation period was extended'),
                'icon' => 'bx-time-five',
                'color' => 'warning',
                'metadata' => [
                    'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
                    'new_end_date' => isset($newValues['probation_end_date']) ? Carbon::parse($newValues['probation_end_date'])->format('d-m-Y') : null,
                ],
            ];
        }

        return null;
    }

    /**
     * Get status change event details.
     */
    protected function getStatusChangeEvent(Audit $audit, string $oldStatus, string $newStatus): array
    {
        $statusMap = [
            UserAccountStatus::ACTIVE->value => ['label' => 'Active', 'icon' => 'bx-check-circle', 'color' => 'success'],
            UserAccountStatus::INACTIVE->value => ['label' => 'Inactive', 'icon' => 'bx-x-circle', 'color' => 'secondary'],
            UserAccountStatus::SUSPENDED->value => ['label' => 'Suspended', 'icon' => 'bx-pause-circle', 'color' => 'warning'],
            UserAccountStatus::TERMINATED->value => ['label' => 'Terminated', 'icon' => 'bx-user-x', 'color' => 'danger'],
            UserAccountStatus::RELIEVED->value => ['label' => 'Relieved', 'icon' => 'bx-user-minus', 'color' => 'danger'],
            UserAccountStatus::RETIRED->value => ['label' => 'Retired', 'icon' => 'bx-home-heart', 'color' => 'primary'],
            UserAccountStatus::PROBATION_FAILED->value => ['label' => 'Probation Failed', 'icon' => 'bx-error-circle', 'color' => 'danger'],
        ];

        $newStatusInfo = $statusMap[$newStatus] ?? ['label' => ucfirst($newStatus), 'icon' => 'bx-info-circle', 'color' => 'info'];

        return [
            'date' => $audit->created_at->format('Y-m-d H:i:s'),
            'type' => 'status_change',
            'title' => __('Status Changed to :status', ['status' => $newStatusInfo['label']]),
            'description' => __('Employee status changed from :old to :new', [
                'old' => $statusMap[$oldStatus]['label'] ?? ucfirst($oldStatus),
                'new' => $newStatusInfo['label'],
            ]),
            'icon' => $newStatusInfo['icon'],
            'color' => $newStatusInfo['color'],
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
            ],
        ];
    }

    /**
     * Get team change event details.
     */
    protected function getTeamChangeEvent(Audit $audit, int $oldTeamId, int $newTeamId): array
    {
        $oldTeam = DB::table('teams')->where('id', $oldTeamId)->value('name');
        $newTeam = DB::table('teams')->where('id', $newTeamId)->value('name');

        return [
            'date' => $audit->created_at->format('Y-m-d H:i:s'),
            'type' => 'team_changed',
            'title' => __('Team Changed'),
            'description' => __('Employee moved from :old to :new', [
                'old' => $oldTeam ?? 'Unknown',
                'new' => $newTeam ?? 'Unknown',
            ]),
            'icon' => 'bx-group',
            'color' => 'info',
            'metadata' => [
                'old_team' => $oldTeam,
                'new_team' => $newTeam,
                'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
            ],
        ];
    }

    /**
     * Get designation change event details.
     */
    protected function getDesignationChangeEvent(Audit $audit, int $oldDesignationId, int $newDesignationId): array
    {
        $oldDesignation = DB::table('designations')->where('id', $oldDesignationId)->value('name');
        $newDesignation = DB::table('designations')->where('id', $newDesignationId)->value('name');

        return [
            'date' => $audit->created_at->format('Y-m-d H:i:s'),
            'type' => 'designation_changed',
            'title' => __('Designation Changed'),
            'description' => __('Designation changed from :old to :new', [
                'old' => $oldDesignation ?? 'Unknown',
                'new' => $newDesignation ?? 'Unknown',
            ]),
            'icon' => 'bx-briefcase',
            'color' => 'primary',
            'metadata' => [
                'old_designation' => $oldDesignation,
                'new_designation' => $newDesignation,
                'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
            ],
        ];
    }

    /**
     * Get salary change event details.
     */
    protected function getSalaryChangeEvent(Audit $audit, float $oldSalary, float $newSalary): array
    {
        $percentageChange = $oldSalary > 0 ? round((($newSalary - $oldSalary) / $oldSalary) * 100, 2) : 0;
        $isIncrease = $newSalary > $oldSalary;

        return [
            'date' => $audit->created_at->format('Y-m-d H:i:s'),
            'type' => 'salary_changed',
            'title' => __('Base Salary Changed'),
            'description' => __('Base salary :change from :old to :new (:percentage%)', [
                'change' => $isIncrease ? 'increased' : 'decreased',
                'old' => number_format($oldSalary, 2),
                'new' => number_format($newSalary, 2),
                'percentage' => abs($percentageChange),
            ]),
            'icon' => $isIncrease ? 'bx-trending-up' : 'bx-trending-down',
            'color' => $isIncrease ? 'success' : 'warning',
            'metadata' => [
                'old_salary' => $oldSalary,
                'new_salary' => $newSalary,
                'percentage_change' => $percentageChange,
                'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
            ],
        ];
    }

    /**
     * Get reporting manager change event details.
     */
    protected function getReportingManagerChangeEvent(Audit $audit, ?int $oldManagerId, ?int $newManagerId): array
    {
        $oldManager = $oldManagerId ? DB::table('users')->where('id', $oldManagerId)->value(DB::raw("CONCAT(first_name, ' ', last_name)")) : null;
        $newManager = $newManagerId ? DB::table('users')->where('id', $newManagerId)->value(DB::raw("CONCAT(first_name, ' ', last_name)")) : null;

        return [
            'date' => $audit->created_at->format('Y-m-d H:i:s'),
            'type' => 'reporting_manager_changed',
            'title' => __('Reporting Manager Changed'),
            'description' => __('Reporting manager changed from :old to :new', [
                'old' => $oldManager ?? 'None',
                'new' => $newManager ?? 'None',
            ]),
            'icon' => 'bx-user-voice',
            'color' => 'info',
            'metadata' => [
                'old_manager' => $oldManager,
                'new_manager' => $newManager,
                'updated_by' => $audit->user ? $audit->user->getFullName() : 'System',
            ],
        ];
    }
}
