<?php

namespace App\Notifications\Employee;

use App\Enums\NotificationPreferenceType;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmployeeTerminatedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected User $employee;

    protected array $terminationData;

    public function __construct(User $employee, array $terminationData)
    {
        $this->employee = $employee;
        $this->terminationData = $terminationData;
    }

    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::EMPLOYEE_TERMINATED;
    }

    protected function getDefaultChannels(): array
    {
        return ['fcm', 'database', 'mail'];
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Employment Termination Notice',
            'body' => "Your employment will end on {$this->terminationData['last_working_day']}",
            'data' => [
                'type' => 'termination',
                'employee_id' => (string) $this->employee->id,
                'exit_date' => $this->terminationData['exit_date'],
                'last_working_day' => $this->terminationData['last_working_day'],
            ],
            'options' => ['priority' => 'high'],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Employment Terminated',
            'message' => "Last working day: {$this->terminationData['last_working_day']}",
            'type' => 'termination',
            'data' => $this->terminationData,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Employment Termination Notice')
            ->greeting("Hello {$this->employee->first_name},")
            ->line("Your employment with us will end on {$this->terminationData['last_working_day']}.")
            ->line("Exit Date: {$this->terminationData['exit_date']}")
            ->line("Reason: {$this->terminationData['exit_reason']}")
            ->line('Please contact HR for any questions regarding your exit process.')
            ->line('Thank you for your service.');
    }
}
