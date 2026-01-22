<?php

namespace App\Traits;

use App\Enums\LifecycleEventType;
use App\Models\EmployeeLifecycleEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait LogsLifecycleEvents
{
    /**
     * Log a lifecycle event for the user.
     */
    public function logLifecycleEvent(
        LifecycleEventType|string $eventType,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?array $metadata = null,
        ?string $notes = null,
        ?\DateTimeInterface $eventDate = null,
        ?int $triggeredById = null
    ): EmployeeLifecycleEvent {
        $eventType = $eventType instanceof LifecycleEventType ? $eventType : LifecycleEventType::from($eventType);

        return EmployeeLifecycleEvent::create([
            'user_id' => $this->id,
            'event_type' => $eventType,
            'event_date' => $eventDate ?? now(),
            'triggered_by_id' => $triggeredById ?? Auth::id(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata,
            'notes' => $notes,
            'notification_sent' => false,
        ]);
    }

    /**
     * Mark lifecycle event notification as sent.
     */
    public function markLifecycleEventNotificationSent(int $eventId): void
    {
        EmployeeLifecycleEvent::where('id', $eventId)
            ->update([
                'notification_sent' => true,
                'notification_sent_at' => now(),
            ]);
    }

    /**
     * Get all lifecycle events for this user.
     */
    public function lifecycleEvents()
    {
        return $this->hasMany(EmployeeLifecycleEvent::class, 'user_id');
    }

    /**
     * Get lifecycle events of a specific type.
     */
    public function lifecycleEventsOfType(LifecycleEventType|string $type)
    {
        return $this->lifecycleEvents()->ofType($type);
    }

    /**
     * Get lifecycle events in a specific category.
     */
    public function lifecycleEventsOfCategory(string $category)
    {
        return $this->lifecycleEvents()->ofCategory($category);
    }

    /**
     * Get the most recent lifecycle event.
     */
    public function latestLifecycleEvent()
    {
        return $this->lifecycleEvents()->latest('event_date')->first();
    }

    /**
     * Get lifecycle events within a date range.
     */
    public function lifecycleEventsBetween($startDate, $endDate)
    {
        return $this->lifecycleEvents()->betweenDates($startDate, $endDate);
    }

    /**
     * Quick helper methods for common events.
     */
    public function logJoined(?string $notes = null): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::JOINED,
            metadata: ['date_of_joining' => $this->date_of_joining],
            notes: $notes ?? __('Employee joined the organization'),
            eventDate: $this->date_of_joining ? Carbon::parse($this->date_of_joining) : now()
        );
    }

    public function logProbationConfirmed(?string $notes = null): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::PROBATION_CONFIRMED,
            metadata: [
                'probation_end_date' => $this->probation_end_date?->format('Y-m-d'),
                'confirmed_at' => now()->format('Y-m-d H:i:s'),
            ],
            notes: $notes ?? __('Probation period successfully completed')
        );
    }

    public function logProbationExtended(int $extensionMonths, ?string $reason = null): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::PROBATION_EXTENDED,
            metadata: [
                'extension_months' => $extensionMonths,
                'new_end_date' => $this->probation_end_date?->format('Y-m-d'),
                'reason' => $reason,
            ],
            notes: $reason ?? __('Probation period extended by :months month(s)', ['months' => $extensionMonths])
        );
    }

    public function logProbationFailed(?string $reason = null): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::PROBATION_FAILED,
            metadata: [
                'failure_reason' => $reason,
                'last_working_day' => $this->last_working_day,
            ],
            notes: $reason ?? __('Probation period not confirmed')
        );
    }

    public function logTermination(array $terminationData): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::TERMINATED,
            metadata: $terminationData,
            notes: __('Employee terminated - :reason', ['reason' => $terminationData['exit_reason'] ?? 'Unknown'])
        );
    }

    public function logTeamChange(int $oldTeamId, int $newTeamId): EmployeeLifecycleEvent
    {
        $oldTeam = \App\Models\Team::find($oldTeamId)?->name ?? 'Unknown';
        $newTeam = \App\Models\Team::find($newTeamId)?->name ?? 'Unknown';

        return $this->logLifecycleEvent(
            LifecycleEventType::TEAM_CHANGED,
            oldValue: ['team_id' => $oldTeamId],
            newValue: ['team_id' => $newTeamId],
            metadata: ['old_team' => $oldTeam, 'new_team' => $newTeam],
            notes: __('Team changed from :old to :new', ['old' => $oldTeam, 'new' => $newTeam])
        );
    }

    public function logDesignationChange(int $oldDesignationId, int $newDesignationId): EmployeeLifecycleEvent
    {
        $oldDesignation = \App\Models\Designation::find($oldDesignationId)?->name ?? 'Unknown';
        $newDesignation = \App\Models\Designation::find($newDesignationId)?->name ?? 'Unknown';

        return $this->logLifecycleEvent(
            LifecycleEventType::DESIGNATION_CHANGED,
            oldValue: ['designation_id' => $oldDesignationId],
            newValue: ['designation_id' => $newDesignationId],
            metadata: ['old_designation' => $oldDesignation, 'new_designation' => $newDesignation],
            notes: __('Designation changed from :old to :new', ['old' => $oldDesignation, 'new' => $newDesignation])
        );
    }

    public function logStatusChange(string $oldStatus, string $newStatus): EmployeeLifecycleEvent
    {
        return $this->logLifecycleEvent(
            LifecycleEventType::STATUS_CHANGED,
            oldValue: ['status' => $oldStatus],
            newValue: ['status' => $newStatus],
            metadata: ['old_status' => $oldStatus, 'new_status' => $newStatus],
            notes: __('Status changed from :old to :new', ['old' => $oldStatus, 'new' => $newStatus])
        );
    }
}
