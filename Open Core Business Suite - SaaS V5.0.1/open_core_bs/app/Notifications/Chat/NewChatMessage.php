<?php

namespace App\Notifications\Chat;

use App\Enums\NotificationPreferenceType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

/**
 * Notification sent when a new chat message is received
 *
 * Channels: FCM + Database (no email to avoid spam)
 * Queue: NO (real-time notification)
 */
class NewChatMessage extends BaseNotification
{
    /**
     * The chat message instance
     */
    protected $message;

    /**
     * Create a new notification instance
     */
    public function __construct($message)
    {
        $this->message = $message;

        // Don't queue chat messages - they should be sent immediately
        $this->connection = 'sync';
    }

    /**
     * Get the notification preference type
     */
    protected function getNotificationPreferenceType(): NotificationPreferenceType
    {
        return NotificationPreferenceType::CHAT_MESSAGE;
    }

    /**
     * Get the default channels for chat messages
     * Override to ensure chat messages don't go to email
     */
    protected function getDefaultChannels(): array
    {
        return ['fcm', 'database'];
    }

    /**
     * Get the notification's delivery channels (FCM push notification)
     *
     * @param  mixed  $notifiable
     */
    public function toFcm($notifiable): array
    {
        $sender = $this->message->sender ?? $this->message->user;
        $senderName = $sender ? $sender->getFullName() : 'Unknown';

        // Handle different message types
        $preview = $this->getMessagePreview();

        return [
            'title' => $senderName,
            'body' => $preview,
            'data' => [
                'type' => 'chat_message',
                'chat_id' => (string) $this->message->chat_id,
                'message_id' => (string) $this->message->id,
                'sender_id' => (string) ($sender->id ?? ''),
                'timestamp' => $this->message->created_at->toIso8601String(),
            ],
            'options' => [
                'priority' => 'normal', // Chat messages are not urgent
                'sound' => 'message',
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
        $sender = $this->message->sender ?? $this->message->user;
        $senderName = $sender ? $sender->getFullName() : 'Unknown';

        return [
            'type' => 'chat_message',
            'title' => 'New Message',
            'message' => "{$senderName}: ".$this->getMessagePreview(),
            'data' => [
                'chat_id' => $this->message->chat_id,
                'message_id' => $this->message->id,
                'sender' => $this->formatUser($sender),
            ],
            'action_url' => '/chat/'.$this->message->chat_id,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }

    /**
     * Get message preview based on message type
     */
    protected function getMessagePreview(): string
    {
        // Check message type
        $messageType = $this->message->message_type ?? 'text';

        switch ($messageType) {
            case 'file':
            case 'image':
            case 'video':
            case 'audio':
                // For file messages, get the file name if available
                if ($this->message->chatFile) {
                    $fileName = $this->message->chatFile->file_name ?? 'file';

                    return "📎 Sent a file: {$fileName}";
                }

                return '📎 Sent a file';

            case 'location':
                return '📍 Shared location';

            case 'contact':
                return '👤 Shared contact';

            case 'text':
            default:
                // For text messages, truncate content
                // Handle null content gracefully
                if (empty($this->message->content)) {
                    return 'Sent a message';
                }

                return $this->truncate($this->message->content, 80);
        }
    }

    /**
     * Get the broadcastable representation of the notification
     *
     * @param  mixed  $notifiable
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $sender = $this->message->sender ?? $this->message->user;

        return new BroadcastMessage([
            'type' => 'chat_message',
            'message_id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'sender' => $this->formatUser($sender),
            'content' => $this->message->content ?? $this->getMessagePreview(),
            'created_at' => $this->message->created_at->toIso8601String(),
        ]);
    }
}
