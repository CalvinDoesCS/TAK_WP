<?php

namespace App\Enums;

enum ProbationStatus: string
{
    case NOT_APPLICABLE = 'not_applicable';
    case ONGOING = 'ongoing';
    case CONFIRMED = 'confirmed';
    case EXTENDED = 'extended';
    case FAILED = 'failed';

    /**
     * Get the human-readable label for the probation status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => __('Not Applicable'),
            self::ONGOING => __('Ongoing'),
            self::CONFIRMED => __('Confirmed'),
            self::EXTENDED => __('Extended'),
            self::FAILED => __('Failed'),
        };
    }

    /**
     * Get the Bootstrap color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::NOT_APPLICABLE => 'secondary',
            self::ONGOING => 'warning',
            self::CONFIRMED => 'success',
            self::EXTENDED => 'info',
            self::FAILED => 'danger',
        };
    }
}
