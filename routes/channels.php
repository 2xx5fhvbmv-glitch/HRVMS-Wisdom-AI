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

// Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
//     return (int) $user->id === (int) $receiver_id || (int) $user->id === (int) $sender_id;
// });

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    \Log::info('Broadcasting channel for user: ' . $id);
    return (int) $user->id === (int) $id;
});


