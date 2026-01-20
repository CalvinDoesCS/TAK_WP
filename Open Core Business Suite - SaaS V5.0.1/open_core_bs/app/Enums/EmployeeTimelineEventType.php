<?php

namespace App\Enums;

enum EmployeeTimelineEventType: string
{
    case CREATED = 'created';
    case ONBOARDED = 'onboarded';
    case STATUS_CHANGED = 'status_changed';
    case PROBATION_STARTED = 'probation_started';
    case PROBATION_CONFIRMED = 'probation_confirmed';
    case PROBATION_EXTENDED = 'probation_extended';
    case PROBATION_FAILED = 'probation_failed';
    case PROMOTED = 'promoted';
    case SALARY_CHANGED = 'salary_changed';
    case TEAM_CHANGED = 'team_changed';
    case DESIGNATION_CHANGED = 'designation_changed';
    case REPORTING_MANAGER_CHANGED = 'reporting_manager_changed';
    case WARNED = 'warned';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';
    case RELIEVED = 'relieved';
    case RETIRED = 'retired';

    /**
     * Get the human-readable label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => __('Employee Created'),
            self::ONBOARDED => __('Onboarded'),
            self::STATUS_CHANGED => __('Status Changed'),
            self::PROBATION_STARTED => __('Probation Started'),
            self::PROBATION_CONFIRMED => __('Probation Confirmed'),
            self::PROBATION_EXTENDED => __('Probation Extended'),
            self::PROBATION_FAILED => __('Probation Failed'),
            self::PROMOTED => __('Promoted'),
            self::SALARY_CHANGED => __('Salary Changed'),
            self::TEAM_CHANGED => __('Team Changed'),
            self::DESIGNATION_CHANGED => __('Designation Changed'),
            self::REPORTING_MANAGER_CHANGED => __('Reporting Manager Changed'),
            self::WARNED => __('Warning Issued'),
            self::SUSPENDED => __('Suspended'),
            self::TERMINATED => __('Terminated'),
            self::RELIEVED => __('Relieved'),
            self::RETIRED => __('Retired'),
        };
    }

    /**
     * Get the Boxicon class for the event type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'bx bx-user-plus',
            self::ONBOARDED => 'bx bx-badge-check',
            self::STATUS_CHANGED => 'bx bx-refresh',
            self::PROBATION_STARTED => 'bx bx-time-five',
            self::PROBATION_CONFIRMED => 'bx bx-check-circle',
            self::PROBATION_EXTENDED => 'bx bx-calendar-plus',
            self::PROBATION_FAILED => 'bx bx-x-circle',
            self::PROMOTED => 'bx bx-trending-up',
            self::SALARY_CHANGED => 'bx bx-dollar-circle',
            self::TEAM_CHANGED => 'bx bx-group',
            self::DESIGNATION_CHANGED => 'bx bx-id-card',
            self::REPORTING_MANAGER_CHANGED => 'bx bx-sitemap',
            self::WARNED => 'bx bx-error',
            self::SUSPENDED => 'bx bx-pause-circle',
            self::TERMINATED => 'bx bx-user-x',
            self::RELIEVED => 'bx bx-log-out',
            self::RETIRED => 'bx bx-home-smile',
        };
    }

    /**
     * Get the Bootstrap color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::CREATED => 'primary',
            self::ONBOARDED => 'success',
            self::STATUS_CHANGED => 'info',
            self::PROBATION_STARTED => 'warning',
            self::PROBATION_CONFIRMED => 'success',
            self::PROBATION_EXTENDED => 'info',
            self::PROBATION_FAILED => 'danger',
            self::PROMOTED => 'success',
            self::SALARY_CHANGED => 'primary',
            self::TEAM_CHANGED => 'info',
            self::DESIGNATION_CHANGED => 'info',
            self::REPORTING_MANAGER_CHANGED => 'info',
            self::WARNED => 'warning',
            self::SUSPENDED => 'warning',
            self::TERMINATED => 'danger',
            self::RELIEVED => 'secondary',
            self::RETIRED => 'secondary',
        };
    }
}
