<?php

namespace App\Notifications\Employee;

use App\Enums\NotificationPreferenceType;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ProbationConfirmedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected User $employee;

    protected string $confirmedDate;

    public function __construct(User $employee, string $confirmedDate)
    {
        $this->employee = $employee;
        $this->confirmedDate = $confirmedDate;
    }

    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::PROBATION_CONFIRMED;
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Probation Confirmed!',
            'body' => 'Congratulations! Your probation period has been successfully confirmed.',
            'data' => [
                'type' => 'probation_confirmed',
                'employee_id' => (string) $this->employee->id,
                'confirmed_date' => $this->confirmedDate,
            ],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Probation Confirmed',
            'message' => 'Your probation period has been successfully confirmed.',
            'type' => 'probation_confirmed',
            'data' => [
                'employee_id' => $this->employee->id,
                'confirmed_date' => $this->confirmedDate,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Probation Period Confirmed')
            ->greeting("Hello {$this->employee->first_name}!")
            ->line('Congratulations! Your probation period has been successfully confirmed.')
            ->line("Confirmed on: {$this->confirmedDate}")
            ->line('You are now a permanent member of our team.')
            ->action('View Profile', url('/employees/'.$this->employee->id))
            ->line('Keep up the great work!');
    }
}
