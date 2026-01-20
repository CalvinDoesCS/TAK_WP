<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\FieldManager\App\Models\Visit;

class NewVisit extends Notification
{
    use Queueable;

    private Visit $visit;

    /**
     * Create a new notification instance.
     */
    public function __construct(Visit $visit)
    {
        $this->visit = $visit;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toDatabase(object $notifiable): array
    {

        return [
            'title' => 'New Client Visit',
            'message' => 'New visit created by '.auth()->user()->getFullName(),
            'id' => $this->visit->id,
        ];
    }

    public function toFcm($notifiable)
    {
        return [
            'title' => 'New Client Visit',
            'body' => 'New visit created by '.auth()->user()->getFullName(),
        ];
    }
}
