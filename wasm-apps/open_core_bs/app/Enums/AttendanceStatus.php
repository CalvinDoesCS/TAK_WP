<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case ABSENT = 'absent';
    case LEAVE = 'leave';
    case HOLIDAY = 'holiday';
    case WEEKEND = 'weekend';
    case HALF_DAY = 'half_day';

    /**
     * Get label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::CHECKED_IN => 'Checked In',
            self::CHECKED_OUT => 'Checked Out',
            self::ABSENT => 'Absent',
            self::LEAVE => 'On Leave',
            self::HOLIDAY => 'Holiday',
            self::WEEKEND => 'Weekend',
            self::HALF_DAY => 'Half Day',
        };
    }

    /**
     * Get badge class for the status
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::CHECKED_IN => 'bg-label-info',
            self::CHECKED_OUT => 'bg-label-success',
            self::ABSENT => 'bg-label-danger',
            self::LEAVE => 'bg-label-warning',
            self::HOLIDAY => 'bg-label-primary',
            self::WEEKEND => 'bg-label-secondary',
            self::HALF_DAY => 'bg-label-warning',
        };
    }

    /**
     * Check if status indicates presence
     */
    public function isPresent(): bool
    {
        return in_array($this, [
            self::CHECKED_IN,
            self::CHECKED_OUT,
            self::HALF_DAY,
        ]);
    }

    /**
     * Get statuses that indicate present
     */
    public static function presentStatuses(): array
    {
        return [
            self::CHECKED_IN,
            self::CHECKED_OUT,
            self::HALF_DAY,
        ];
    }
}
