<?php

namespace App\Enums;

enum TerminationType: string
{
    case RESIGNATION = 'resignation';
    case TERMINATION = 'termination';
    case TERMINATED_WITH_CAUSE = 'terminated_with_cause';
    case TERMINATED_WITHOUT_CAUSE = 'terminated_without_cause';
    case RETIREMENT = 'retirement';
    case CONTRACT_END = 'contract_end';
    case ABSCONDING = 'absconding';
    case MUTUAL_SEPARATION = 'mutual_separation';
    case LAYOFF = 'layoff';
    case PROBATION_FAILED = 'probation_failed';

    /**
     * Get the human-readable label for the termination type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RESIGNATION => __('Resignation'),
            self::TERMINATION => __('Termination'),
            self::TERMINATED_WITH_CAUSE => __('Terminated with Cause'),
            self::TERMINATED_WITHOUT_CAUSE => __('Terminated without Cause'),
            self::RETIREMENT => __('Retirement'),
            self::CONTRACT_END => __('Contract End'),
            self::ABSCONDING => __('Absconding'),
            self::MUTUAL_SEPARATION => __('Mutual Separation'),
            self::LAYOFF => __('Layoff'),
            self::PROBATION_FAILED => __('Probation Failed'),
        };
    }
}
