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
}, ['guards' => ['resort-admin', 'api']]);

Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {
    return (int) $user->id === (int) $receiver_id;
}, ['guards' => ['resort-admin', 'api']]);

// Support ticket live chat (Admin + Resort panels). Scoped per ticket, not
// per user id, since one ticket's admin and employee sides must share a
// channel without colliding with unrelated users of the same numeric id.
Broadcast::channel('support-ticket.{supportId}', function ($user, $supportId) {
    if ($user instanceof \App\Models\Admin) {
        // Audit run-3 §4: an admin needs the Support module's View permission (any admin used to be admitted).
        return $user->type === 'super' || \App\Models\AdminRoleModulePermission::where('role_id', $user->role_id)
            ->whereHas('module_permission', fn ($q) => $q->where('module_id', config('settings.admin_modules.supports'))
                ->where('permission_id', config('settings.permissions.view')))->exists();
    }
    return \App\Models\Support::where('id', $supportId)->where('resort_id', $user->resort_id)->exists();
}, ['guards' => ['admin', 'resort-admin']]);

Broadcast::channel('group.{group_id}', function ($user, $group_id) {
    $isMember = \App\Models\GroupChatMember::where('chat_group_id', $group_id)
        ->where('user_id', $user->id)
        ->exists();
    return $isMember ? ['id' => $user->id, 'name' => $user->full_name] : false;
}, ['guards' => ['resort-admin', 'api']]);

// Presence roster only — join on login/foreground, leave on background.
// Pusher's member_added/member_removed events on this channel ARE the
// online-status list; no backend event/table needed for "who's online".
// Resort notification cards (ResortNotificationEvent and siblings): each resort's own users only.
Broadcast::channel('resort-notifications.{resortId}', function ($user, $resortId) {
    return (int) $user->resort_id === (int) $resortId;
}, ['guards' => ['resort-admin', 'api']]);
// One person's notifications (ResortNotificationEvent with `sendto`): that employee only (audit run-3 M1).
Broadcast::channel('resort-notifications.{resortId}.employee.{employeeId}', function ($user, $resortId, $employeeId) {
    return (int) $user->resort_id === (int) $resortId && (int) optional($user->GetEmployee)->id === (int) $employeeId;
}, ['guards' => ['resort-admin', 'api']]);

Broadcast::channel('resort-online.{resort_id}', function ($user, $resort_id) {
    return (int) $user->resort_id === (int) $resort_id
        ? ['id' => $user->id, 'name' => $user->full_name]
        : false;
});

// SOS dashboard real-time alert — any resort-admin/employee of this resort
// can listen, matching resort-online.{resort_id}'s same resort_id check.
Broadcast::channel('resort.{resort_id}.sos', function ($user, $resort_id) {
    return (int) $user->resort_id === (int) $resort_id;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    \Log::info('Broadcasting channel for user: ' . $id);
    return (int) $user->id === (int) $id;
}, ['guards' => ['resort-admin', 'api']]);


