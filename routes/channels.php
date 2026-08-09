<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::channel('conversation.{receiver_id}', function ($user, $receiver_id) {
   return (int) $user->id === (int) $receiver_id;
});

Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
    return (int) $user->id === (int) $receiver_id;
});

Broadcast::channel('group.{group_id}', function ($user, $group_id) {
    $isMember = \App\Models\GroupChatMember::where('chat_group_id', $group_id)
        ->where('user_id', $user->id)
        ->exists();
    return $isMember ? ['id' => $user->id, 'name' => $user->full_name] : false;
});

// Presence roster only — join on login/foreground, leave on background.
// Pusher's member_added/member_removed events on this channel ARE the
// online-status list; no backend event/table needed for "who's online".
Broadcast::channel('resort-online.{resort_id}', function ($user, $resort_id) {
    return (int) $user->resort_id === (int) $resort_id
        ? ['id' => $user->id, 'name' => $user->full_name]
        : false;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    \Log::info('Broadcasting channel for user: ' . $id);
    return (int) $user->id === (int) $id;
});


