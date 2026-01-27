<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('device-updates', function () {});

/**
 * User Channel Authorization
 *
 * Authorize users to subscribe to their own private user channel.
 * Users can only subscribe to their own user channel for personal notifications.
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    // User can only subscribe to their own channel
    return (int) $user->id === (int) $userId;
});

/**
 * Chat Channel Authorization
 *
 * Authorize users to subscribe to private chat channels.
 * Users can only subscribe to chats they are participants in.
 */
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Check if user is a participant in this chat
    $chat = \Modules\OCConnect\App\Models\Chat::find($chatId);

    if (! $chat) {
        return false;
    }

    // Check if user is a participant
    return $chat->participants()->where('user_id', $user->id)->exists();
});
