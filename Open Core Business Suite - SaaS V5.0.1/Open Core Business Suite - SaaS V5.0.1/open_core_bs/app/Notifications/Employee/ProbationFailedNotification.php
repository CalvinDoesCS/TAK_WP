<?php

namespace App\Notifications\Employee;

use App\Enums\NotificationPreferenceType;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ProbationFailedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected User $employee;

    protected string $reason;

    protected string $lastWorkingDay;

    public function __construct(User $employee, string $reason, string $lastWorkingDay)
    {
        $this->employee = $employee;
        $this->reason = $reason;
        $this->lastWorkingDay = $lastWorkingDay;
    }

    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::PROBATION_FAILED;
    }

    protected function getDefaultChannels(): array
    {
        return ['fcm', 'database', 'mail'];
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Probation Period Update',
            'body' => "Your probation period was not confirmed. Last working day: {$this->lastWorkingDay}",
            'data' => [
                'type' => 'probation_failed',
                'employee_id' => (string) $this->employee->id,
                'last_working_day' => $this->lastWorkingDay,
            ],
            'options' => ['priority' => 'high'],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Probation Period Update',
            'message' => "Your probation period was not confirmed. Last working day: {$this->lastWorkingDay}",
            'type' => 'probation_failed',
            'data' => [
                'employee_id' => $this->employee->id,
                'reason' => $this->reason,
                'last_working_day' => $this->lastWorkingDay,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Probation Period Update')
            ->greeting("Hello {$this->employee->first_name},")
            ->line('Unfortunately, your probation period was not confirmed.')
            ->line("Reason: {$this->reason}")
            ->line("Last Working Day: {$this->lastWorkingDay}")
            ->line('Please contact HR for further details.')
            ->line('Thank you.');
    }
}
