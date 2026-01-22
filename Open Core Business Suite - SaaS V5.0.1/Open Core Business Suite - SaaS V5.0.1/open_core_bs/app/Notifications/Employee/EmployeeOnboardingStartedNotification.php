<?php

namespace App\Notifications\Employee;

use App\Enums\NotificationPreferenceType;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class EmployeeOnboardingStartedNotification extends BaseNotification
{
    protected User $employee;

    public function __construct(User $employee)
    {
        $this->employee = $employee;
        $this->connection = 'sync'; // Real-time
    }

    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::EMPLOYEE_ONBOARDING;
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Welcome! Your Onboarding Has Started',
            'body' => 'Please complete your onboarding checklist to get started.',
            'data' => [
                'type' => 'onboarding_started',
                'employee_id' => (string) $this->employee->id,
            ],
        ];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Onboarding Started',
            'message' => 'Your onboarding process has been initiated.',
            'type' => 'onboarding_started',
            'data' => ['employee_id' => $this->employee->id],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to the Team!')
            ->greeting("Hello {$this->employee->first_name}!")
            ->line('Your onboarding process has started.')
            ->line('Please complete your profile and onboarding checklist.')
            ->action('View Onboarding', url('/employees/'.$this->employee->id))
            ->line('Welcome aboard!');
    }
}
