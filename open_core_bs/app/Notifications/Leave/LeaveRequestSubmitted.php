<?php

namespace App\Notifications\Leave;

use App\Enums\NotificationPreferenceType;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification sent to approver when a leave request is submitted
 *
 * Channels: FCM + Database + Email
 * Queue: YES (can be queued for performance)
 */
class LeaveRequestSubmitted extends BaseNotification implements ShouldQueue
{
    /**
     * The leave request instance
     */
    protected $leave;

    /**
     * Create a new notification instance
     */
    public function __construct($leave)
    {
        $this->leave = $leave;
    }

    /**
     * Get the notification preference type
     */
    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::LEAVE_REQUEST;
    }

    /**
     * Get the default channels for leave request
     */
    protected function getDefaultChannels(): array
    {
        return ['fcm', 'database', 'mail'];
    }

    /**
     * Get the notification's delivery channels (FCM push notification)
     *
     * @param  mixed  $notifiable
     */
    public function toFcm($notifiable): array
    {
        $employee = $this->leave->user;
        $employeeName = $employee ? $employee->getFullName() : 'An employee';
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');

        return [
            'title' => '📝 New Leave Request',
            'body' => "{$employeeName} has requested leave from {$startDate} to {$endDate}",
            'data' => [
                'type' => 'leave_request',
                'leave_id' => (string) $this->leave->id,
                'employee_id' => (string) $this->leave->user_id,
                'start_date' => $this->leave->from_date->toDateString(),
                'end_date' => $this->leave->to_date->toDateString(),
                'duration' => (string) $this->leave->duration,
            ],
            'options' => [
                'priority' => 'high',
                'sound' => 'notification',
            ],
        ];
    }

    /**
     * Get the array representation of the notification (Database)
     *
     * @param  mixed  $notifiable
     */
    public function toDatabase($notifiable): array
    {
        $employee = $this->leave->user;
        $employeeName = $employee ? $employee->getFullName() : 'An employee';
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');

        return [
            'type' => 'leave_request',
            'title' => 'New Leave Request',
            'message' => "{$employeeName} has requested leave from {$startDate} to {$endDate}",
            'data' => [
                'leave_id' => $this->leave->id,
                'employee' => $this->formatUser($employee),
                'start_date' => $this->leave->from_date->toDateString(),
                'end_date' => $this->leave->to_date->toDateString(),
                'duration' => $this->leave->duration,
                'leave_type' => $this->leave->leaveType->name ?? '',
                'reason' => $this->leave->reason,
            ],
            'action_url' => '/leave/requests/'.$this->leave->id,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the mail representation of the notification
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $employee = $this->leave->user;
        $employeeName = $employee ? $employee->getFullName() : 'An employee';
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');

        $mail = (new MailMessage)
            ->subject('New Leave Request - Approval Required')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line('A new leave request requires your approval.')
            ->line("**Employee:** {$employeeName}")
            ->line('**Leave Type:** '.($this->leave->leaveType->name ?? 'N/A'))
            ->line("**Duration:** {$startDate} to {$endDate} ({$this->leave->duration} days)");

        if ($this->leave->reason) {
            $mail->line("**Reason:** {$this->leave->reason}");
        }

        return $mail
            ->action('Review Leave Request', url('/leave/requests/'.$this->leave->id))
            ->line('Please review and approve or reject this request.');
    }
}
