<?php

namespace App\Enums;

enum LifecycleEventType: string
{
    // Onboarding & Joining
    case CREATED = 'created';
    case JOINED = 'joined';
    case ONBOARDING_STARTED = 'onboarding_started';
    case ONBOARDING_COMPLETED = 'onboarding_completed';

    // Probation
    case PROBATION_STARTED = 'probation_started';
    case PROBATION_CONFIRMED = 'probation_confirmed';
    case PROBATION_EXTENDED = 'probation_extended';
    case PROBATION_FAILED = 'probation_failed';

    // Career Changes
    case PROMOTED = 'promoted';
    case DEMOTED = 'demoted';
    case TEAM_CHANGED = 'team_changed';
    case DESIGNATION_CHANGED = 'designation_changed';
    case REPORTING_MANAGER_CHANGED = 'reporting_manager_changed';
    case LOCATION_CHANGED = 'location_changed';

    // Compensation
    case SALARY_CHANGED = 'salary_changed';
    case BONUS_AWARDED = 'bonus_awarded';
    case INCENTIVE_AWARDED = 'incentive_awarded';

    // Status Changes
    case STATUS_CHANGED = 'status_changed';
    case ACTIVATED = 'activated';
    case DEACTIVATED = 'deactivated';
    case SUSPENDED = 'suspended';
    case UNSUSPENDED = 'unsuspended';

    // Disciplinary
    case WARNING_ISSUED = 'warning_issued';
    case PENALTY_APPLIED = 'penalty_applied';

    // Exit & Termination
    case RESIGNATION_SUBMITTED = 'resignation_submitted';
    case RESIGNATION_ACCEPTED = 'resignation_accepted';
    case TERMINATION_INITIATED = 'termination_initiated';
    case TERMINATED = 'terminated';
    case RELIEVED = 'relieved';
    case RETIRED = 'retired';

    // Leave & Attendance
    case LEAVE_BALANCE_ADJUSTED = 'leave_balance_adjusted';
    case ATTENDANCE_TYPE_CHANGED = 'attendance_type_changed';

    // Documents
    case DOCUMENT_UPLOADED = 'document_uploaded';
    case DOCUMENT_VERIFIED = 'document_verified';
    case DOCUMENT_EXPIRED = 'document_expired';

    // Other
    case EMERGENCY_CONTACT_UPDATED = 'emergency_contact_updated';
    case BANK_ACCOUNT_UPDATED = 'bank_account_updated';
    case PROFILE_UPDATED = 'profile_updated';
    case REHIRED = 'rehired';

    /**
     * Get human-readable label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => __('Employee Record Created'),
            self::JOINED => __('Employee Joined'),
            self::ONBOARDING_STARTED => __('Onboarding Started'),
            self::ONBOARDING_COMPLETED => __('Onboarding Completed'),

            self::PROBATION_STARTED => __('Probation Started'),
            self::PROBATION_CONFIRMED => __('Probation Confirmed'),
            self::PROBATION_EXTENDED => __('Probation Extended'),
            self::PROBATION_FAILED => __('Probation Failed'),

            self::PROMOTED => __('Promoted'),
            self::DEMOTED => __('Demoted'),
            self::TEAM_CHANGED => __('Team Changed'),
            self::DESIGNATION_CHANGED => __('Designation Changed'),
            self::REPORTING_MANAGER_CHANGED => __('Reporting Manager Changed'),
            self::LOCATION_CHANGED => __('Location Changed'),

            self::SALARY_CHANGED => __('Salary Changed'),
            self::BONUS_AWARDED => __('Bonus Awarded'),
            self::INCENTIVE_AWARDED => __('Incentive Awarded'),

            self::STATUS_CHANGED => __('Status Changed'),
            self::ACTIVATED => __('Activated'),
            self::DEACTIVATED => __('Deactivated'),
            self::SUSPENDED => __('Suspended'),
            self::UNSUSPENDED => __('Unsuspended'),

            self::WARNING_ISSUED => __('Warning Issued'),
            self::PENALTY_APPLIED => __('Penalty Applied'),

            self::RESIGNATION_SUBMITTED => __('Resignation Submitted'),
            self::RESIGNATION_ACCEPTED => __('Resignation Accepted'),
            self::TERMINATION_INITIATED => __('Termination Initiated'),
            self::TERMINATED => __('Terminated'),
            self::RELIEVED => __('Relieved'),
            self::RETIRED => __('Retired'),

            self::LEAVE_BALANCE_ADJUSTED => __('Leave Balance Adjusted'),
            self::ATTENDANCE_TYPE_CHANGED => __('Attendance Type Changed'),

            self::DOCUMENT_UPLOADED => __('Document Uploaded'),
            self::DOCUMENT_VERIFIED => __('Document Verified'),
            self::DOCUMENT_EXPIRED => __('Document Expired'),

            self::EMERGENCY_CONTACT_UPDATED => __('Emergency Contact Updated'),
            self::BANK_ACCOUNT_UPDATED => __('Bank Account Updated'),
            self::PROFILE_UPDATED => __('Profile Updated'),
            self::REHIRED => __('Rehired'),
        };
    }

    /**
     * Get Boxicon class for the event type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATED, self::JOINED => 'bx-user-plus',
            self::ONBOARDING_STARTED, self::ONBOARDING_COMPLETED => 'bx-clipboard-check',

            self::PROBATION_STARTED => 'bx-time-five',
            self::PROBATION_CONFIRMED => 'bx-check-circle',
            self::PROBATION_EXTENDED => 'bx-calendar-plus',
            self::PROBATION_FAILED => 'bx-error-circle',

            self::PROMOTED => 'bx-up-arrow-circle',
            self::DEMOTED => 'bx-down-arrow-circle',
            self::TEAM_CHANGED => 'bx-group',
            self::DESIGNATION_CHANGED => 'bx-briefcase',
            self::REPORTING_MANAGER_CHANGED => 'bx-user-voice',
            self::LOCATION_CHANGED => 'bx-map',

            self::SALARY_CHANGED => 'bx-dollar-circle',
            self::BONUS_AWARDED, self::INCENTIVE_AWARDED => 'bx-gift',

            self::STATUS_CHANGED => 'bx-refresh',
            self::ACTIVATED => 'bx-check-circle',
            self::DEACTIVATED => 'bx-x-circle',
            self::SUSPENDED => 'bx-pause-circle',
            self::UNSUSPENDED => 'bx-play-circle',

            self::WARNING_ISSUED => 'bx-error',
            self::PENALTY_APPLIED => 'bx-error-alt',

            self::RESIGNATION_SUBMITTED, self::RESIGNATION_ACCEPTED => 'bx-log-out',
            self::TERMINATION_INITIATED, self::TERMINATED => 'bx-user-x',
            self::RELIEVED => 'bx-user-minus',
            self::RETIRED => 'bx-home-heart',

            self::LEAVE_BALANCE_ADJUSTED => 'bx-calendar-edit',
            self::ATTENDANCE_TYPE_CHANGED => 'bx-time',

            self::DOCUMENT_UPLOADED => 'bx-upload',
            self::DOCUMENT_VERIFIED => 'bx-badge-check',
            self::DOCUMENT_EXPIRED => 'bx-calendar-x',

            self::EMERGENCY_CONTACT_UPDATED => 'bx-phone',
            self::BANK_ACCOUNT_UPDATED => 'bx-credit-card',
            self::PROFILE_UPDATED => 'bx-edit',
            self::REHIRED => 'bx-refresh',
        };
    }

    /**
     * Get Bootstrap color class for the event type.
     */
    public function color(): string
    {
        return match ($this) {
            self::CREATED, self::JOINED, self::ONBOARDING_COMPLETED,
            self::PROBATION_CONFIRMED, self::PROMOTED, self::ACTIVATED,
            self::UNSUSPENDED, self::BONUS_AWARDED, self::INCENTIVE_AWARDED,
            self::DOCUMENT_VERIFIED, self::REHIRED => 'success',

            self::PROBATION_FAILED, self::DEMOTED, self::TERMINATED,
            self::WARNING_ISSUED, self::PENALTY_APPLIED, self::DEACTIVATED,
            self::DOCUMENT_EXPIRED => 'danger',

            self::PROBATION_EXTENDED, self::SUSPENDED, self::RESIGNATION_SUBMITTED,
            self::TERMINATION_INITIATED, self::SALARY_CHANGED => 'warning',

            self::TEAM_CHANGED, self::DESIGNATION_CHANGED, self::REPORTING_MANAGER_CHANGED,
            self::LOCATION_CHANGED, self::STATUS_CHANGED, self::ATTENDANCE_TYPE_CHANGED,
            self::LEAVE_BALANCE_ADJUSTED => 'info',

            self::RELIEVED, self::RETIRED => 'primary',

            default => 'secondary',
        };
    }

    /**
     * Get event category for grouping.
     */
    public function category(): string
    {
        return match ($this) {
            self::CREATED, self::JOINED, self::ONBOARDING_STARTED, self::ONBOARDING_COMPLETED => 'onboarding',

            self::PROBATION_STARTED, self::PROBATION_CONFIRMED, self::PROBATION_EXTENDED, self::PROBATION_FAILED => 'probation',

            self::PROMOTED, self::DEMOTED, self::TEAM_CHANGED, self::DESIGNATION_CHANGED,
            self::REPORTING_MANAGER_CHANGED, self::LOCATION_CHANGED => 'career',

            self::SALARY_CHANGED, self::BONUS_AWARDED, self::INCENTIVE_AWARDED => 'compensation',

            self::STATUS_CHANGED, self::ACTIVATED, self::DEACTIVATED, self::SUSPENDED, self::UNSUSPENDED => 'status',

            self::WARNING_ISSUED, self::PENALTY_APPLIED => 'disciplinary',

            self::RESIGNATION_SUBMITTED, self::RESIGNATION_ACCEPTED, self::TERMINATION_INITIATED,
            self::TERMINATED, self::RELIEVED, self::RETIRED => 'exit',

            self::LEAVE_BALANCE_ADJUSTED, self::ATTENDANCE_TYPE_CHANGED => 'attendance',

            self::DOCUMENT_UPLOADED, self::DOCUMENT_VERIFIED, self::DOCUMENT_EXPIRED => 'documents',

            default => 'other',
        };
    }
}
