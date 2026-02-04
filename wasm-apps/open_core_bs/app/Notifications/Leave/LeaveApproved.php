<?php

namespace App\Notifications\Leave;

use App\Enums\NotificationPreferenceType;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification sent when a leave request is approved
 *
 * Channels: FCM + Database + Email
 * Queue: YES (can be queued for performance)
 */
class LeaveApproved extends BaseNotification implements ShouldQueue
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
        return NotificationPreferenceType::LEAVE_APPROVED;
    }

    /**
     * Get the default channels for leave approved
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
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');

        return [
            'title' => '✅ Leave Request Approved',
            'body' => "Your leave request from {$startDate} to {$endDate} has been approved",
            'data' => [
                'type' => 'leave_approved',
                'leave_id' => (string) $this->leave->id,
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
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');

        return [
            'type' => 'leave_approved',
            'title' => 'Leave Request Approved',
            'message' => "Your leave request from {$startDate} to {$endDate} has been approved",
            'data' => [
                'leave_id' => $this->leave->id,
                'start_date' => $this->leave->from_date->toDateString(),
                'end_date' => $this->leave->to_date->toDateString(),
                'duration' => $this->leave->duration,
                'leave_type' => $this->leave->leaveType->name ?? '',
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
        $startDate = $this->leave->from_date->format('M d, Y');
        $endDate = $this->leave->to_date->format('M d, Y');
        $approver = $this->leave->approvedBy ? $this->leave->approvedBy->getFullName() : 'your manager';

        return (new MailMessage)
            ->subject('Leave Request Approved')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line('Good news! Your leave request has been approved.')
            ->line('**Leave Type:** '.($this->leave->leaveType->name ?? 'N/A'))
            ->line("**Duration:** {$startDate} to {$endDate} ({$this->leave->duration} days)")
            ->line("**Approved by:** {$approver}")
            ->action('View Leave Details', url('/leave/requests/'.$this->leave->id))
            ->line('Have a great time off!');
    }
}
