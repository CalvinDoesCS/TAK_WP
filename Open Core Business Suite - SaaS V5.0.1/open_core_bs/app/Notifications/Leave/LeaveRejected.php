<?php

namespace App\Notifications\Leave;

use App\Enums\NotificationPreferenceType;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification sent when a leave request is rejected
 *
 * Channels: FCM + Database + Email
 * Queue: YES (can be queued for performance)
 */
class LeaveRejected extends BaseNotification implements ShouldQueue
{
    /**
     * The leave request instance
     */
    protected $leave;

    /**
     * Rejection reason
     */
    protected $reason;

    /**
     * Create a new notification instance
     */
    public function __construct($leave, $reason = null)
    {
        $this->leave = $leave;
        $this->reason = $reason;
    }

    /**
     * Get the notification preference type
     */
    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::LEAVE_REJECTED;
    }

    /**
     * Get the default channels for leave rejected
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
        $body = $this->reason
          ? "Your leave request was rejected. Reason: {$this->reason}"
          : "Your leave request from {$startDate} to {$endDate} was rejected";

        return [
            'title' => '❌ Leave Request Rejected',
            'body' => $this->truncate($body, 100),
            'data' => [
                'type' => 'leave_rejected',
                'leave_id' => (string) $this->leave->id,
                'start_date' => $this->leave->from_date->toDateString(),
                'end_date' => $this->leave->to_date->toDateString(),
                'reason' => $this->reason ?? '',
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

        $message = "Your leave request from {$startDate} to {$endDate} was rejected";
        if ($this->reason) {
            $message .= ". Reason: {$this->reason}";
        }

        return [
            'type' => 'leave_rejected',
            'title' => 'Leave Request Rejected',
            'message' => $message,
            'data' => [
                'leave_id' => $this->leave->id,
                'start_date' => $this->leave->from_date->toDateString(),
                'end_date' => $this->leave->to_date->toDateString(),
                'duration' => $this->leave->duration,
                'leave_type' => $this->leave->leaveType->name ?? '',
                'reason' => $this->reason,
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
        $rejectedBy = $this->leave->rejectedBy ? $this->leave->rejectedBy->getFullName() : 'your manager';

        $mail = (new MailMessage)
            ->subject('Leave Request Rejected')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line('Your leave request has been rejected.')
            ->line('**Leave Type:** '.($this->leave->leaveType->name ?? 'N/A'))
            ->line("**Duration:** {$startDate} to {$endDate} ({$this->leave->duration} days)")
            ->line("**Rejected by:** {$rejectedBy}");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->action('View Leave Details', url('/leave/requests/'.$this->leave->id))
            ->line('If you have questions, please contact your manager.');
    }
}
