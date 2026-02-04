<?php

namespace App\Events;

use App\Models\UserStatusModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public UserStatusModel $userStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(UserStatusModel $userStatus)
    {
        $this->userStatus = $userStatus->load('user:id,first_name,last_name,code');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user-statuses'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->userStatus->id,
            'user_id' => $this->userStatus->user_id,
            'user' => [
                'id' => $this->userStatus->user->id,
                'name' => $this->userStatus->user->first_name.' '.$this->userStatus->user->last_name,
                'code' => $this->userStatus->user->code,
            ],
            'status' => $this->userStatus->status,
            'message' => $this->userStatus->message,
            'expires_at' => $this->userStatus->expires_at?->toISOString(),
            'status_color' => $this->userStatus->status_color,
            'status_icon' => $this->userStatus->status_icon,
            'updated_at' => $this->userStatus->updated_at->toISOString(),
        ];
    }
}
