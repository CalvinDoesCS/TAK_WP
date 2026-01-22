<?php

namespace App\Models;

use App\Enums\LifecycleEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLifecycleEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'event_date',
        'triggered_by_id',
        'old_value',
        'new_value',
        'metadata',
        'notification_sent',
        'notification_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => LifecycleEventType::class,
            'event_date' => 'datetime',
            'old_value' => 'array',
            'new_value' => 'array',
            'metadata' => 'array',
            'notification_sent' => 'boolean',
            'notification_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the employee (user) for this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who triggered this event.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeOfType($query, LifecycleEventType|string $type)
    {
        $eventType = $type instanceof LifecycleEventType ? $type->value : $type;

        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by event category.
     */
    public function scopeOfCategory($query, string $category)
    {
        $eventTypes = collect(LifecycleEventType::cases())
            ->filter(fn ($type) => $type->category() === $category)
            ->pluck('value')
            ->toArray();

        return $query->whereIn('event_type', $eventTypes);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Get formatted event for timeline display.
     */
    public function toTimelineFormat(): array
    {
        return [
            'date' => $this->event_date->format('Y-m-d H:i:s'),
            'type' => $this->event_type->value,
            'title' => $this->event_type->label(),
            'description' => $this->getDescription(),
            'icon' => $this->event_type->icon(),
            'color' => $this->event_type->color(),
            'metadata' => $this->getFormattedMetadata(),
        ];
    }

    /**
     * Get event description with contextual information.
     */
    protected function getDescription(): string
    {
        $description = $this->notes ?? '';

        if (! $description && $this->metadata) {
            // Generate description from metadata
            $description = $this->generateDescriptionFromMetadata();
        }

        if (! $description) {
            // Fallback to basic description
            $description = $this->event_type->label();
        }

        return $description;
    }

    /**
     * Generate description from metadata.
     */
    protected function generateDescriptionFromMetadata(): string
    {
        $metadata = $this->metadata ?? [];

        return match ($this->event_type) {
            LifecycleEventType::TEAM_CHANGED => __('Changed from :old to :new', [
                'old' => $metadata['old_team'] ?? 'Unknown',
                'new' => $metadata['new_team'] ?? 'Unknown',
            ]),

            LifecycleEventType::DESIGNATION_CHANGED => __('Changed from :old to :new', [
                'old' => $metadata['old_designation'] ?? 'Unknown',
                'new' => $metadata['new_designation'] ?? 'Unknown',
            ]),

            LifecycleEventType::SALARY_CHANGED => __('Changed from :old to :new', [
                'old' => number_format($metadata['old_salary'] ?? 0, 2),
                'new' => number_format($metadata['new_salary'] ?? 0, 2),
            ]),

            LifecycleEventType::STATUS_CHANGED => __('Changed from :old to :new', [
                'old' => $metadata['old_status'] ?? 'Unknown',
                'new' => $metadata['new_status'] ?? 'Unknown',
            ]),

            default => '',
        };
    }

    /**
     * Get formatted metadata for display.
     */
    protected function getFormattedMetadata(): array
    {
        $metadata = $this->metadata ?? [];
        $formatted = [];

        // Add triggered by information
        if ($this->triggeredBy) {
            $formatted[__('Triggered By')] = $this->triggeredBy->getFullName();
        }

        // Add metadata fields
        foreach ($metadata as $key => $value) {
            if (is_scalar($value) && $value !== null && $value !== '') {
                $formatted[ucwords(str_replace('_', ' ', $key))] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Get event color for timeline display
     */
    public function getEventColor(): string
    {
        return $this->event_type->color();
    }

    /**
     * Get event type display label
     */
    public function getEventTypeDisplayAttribute(): string
    {
        return $this->event_type->label();
    }
}
