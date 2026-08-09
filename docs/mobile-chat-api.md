# Chat Module API — replaces the Node `chat-backend` service

For the mobile team. This Laravel API now fully replaces the standalone
Node/Socket.io/MongoDB `chat-backend` service. All chat data was already
stored here (Node never owned any of it — it only relayed realtime delivery
+ push via a webhook). That webhook call is gone; this app now broadcasts
directly over Pusher and sends push itself.

Controllers: `app/Http/Controllers/API/ChatBoat/{ChatController,ConversationController}.php`.
All routes below sit under `auth:api` (Passport Bearer token — same header
you already attach to every other mobile call).

**IDs used throughout this module are `resort_admins.id`, not `employees.id`.**
Every `user_id`/`sender_id`/`receiver_id` you send or receive here is a
resort-admin id.

## REST endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `chat/list` | Your chat list (1:1 + groups), with last message + unread count |
| GET | `chat/faq-list` | Static FAQ content for the chat help screen |
| GET | `chat/start-new/chat?search=` | Employee picker to start a new 1:1 chat — **department-scoped**: HR/GM see the whole resort, everyone else sees only their own department |
| GET | `group/new-employee/list/{group_id}` | Employee picker for adding to an existing group — resort-wide, not department-scoped (deliberate — group membership can cross departments) |
| POST | `create/group-chat` | Create a group. Body: `name` (required), `description`, `members[]` (required, array of resort-admin ids) |
| POST | `group/add-member/{group_id}` | Body: `members[]` (array of resort-admin ids) |
| POST | `group/remove-member/{group_id}` | Body: `member_id` (single id). The group's `admin`-role member cannot be removed. |
| POST | `group/remove/{group_id}` | Deletes the group (and its conversation history). Only the group creator can call this. |
| GET | `chat/view/{type}/{type_id}` | Conversation header (other person/group info) + full message history. `type` is `individual` or `group`; `type_id` is the other resort-admin's id or the group id. |
| GET | `chat/get-messages/{type}/{type_id}` | Alias of `chat/view` — same response, same params. Use whichever reads better client-side. |
| POST | `chat/send-message` | Multipart. Fields: `type` (`individual`\|`group`, required), `type_id` (required), `message` (required string), `attachment` (optional file) |
| GET | `chat/messages/mark-read?conversation_id=` | Marks that conversation thread read for you — **not** a single-message read receipt, it flips your `chat_message_read` row for the whole thread |

`chat/send-message` response:
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {"message_id": 123, "message": "hey"},
  "chat_history": [ {"id":1,"type":"individual","type_id":242,"sender_id":241,"message":"...","attachment":null,"created_at":"..."} ]
}
```

## Realtime — Pusher (replaces the Socket.io connection to Node)

Public client key (safe to ship in the app): `PUSHER_APP_KEY=4390520d21ccdfb45a8f`,
cluster `ap2`. **Never embed the app secret client-side — only the key/cluster above.**

Auth endpoint for private/presence channel subscriptions:
```
POST /api/broadcasting/auth
Authorization: Bearer <same token as every other mobile call>
```
Point your Pusher SDK's `authEndpoint` (or `authorizer`, depending on SDK)
at this URL. The default Laravel `/broadcasting/auth` (no `/api` prefix)
is a *different* route on a session guard — do not use it, it will reject
mobile tokens.

| Channel | Type | Subscribe when | Event |
|---|---|---|---|
| `chat.{myOwnUserId}` | private | Always, on login/app start | `MessageSent` — fires for every 1:1 message sent to you |
| `group.{groupId}` | presence | When you open/are a member of that group | `MessageSent` — fires for every message in that group |
| `resort-online.{resortId}` | presence | On login/app foreground, leave on background | No custom event — the channel's own `member_added`/`member_removed` presence events (built into every Pusher SDK) **are** the online-status roster. Read `channel.members` for who's currently online. |

`MessageSent` payload (same shape for both channel types):
```json
{
  "id": 123,
  "sender_id": 241,
  "message": "hey",
  "type": "individual",
  "type_id": 242,
  "attachment": null,
  "created_at": "2026-08-09 18:31:57"
}
```
For a group message, `type_id` is the group id, not a recipient id.

**Typing indicators**: no backend endpoint — use a Pusher [client event](https://pusher.com/docs/channels/using_channels/events/#triggering-client-events)
directly on `chat.{theirUserId}` or `group.{groupId}`. Convention: event name
`client-typing`, payload `{"user_id": <you>, "type": "individual|group", "type_id": <same as above>}`.
Client events require no server code but do need "client events" enabled on
the Pusher app (already on, same as support-chat uses).

**Last-seen (offline) timestamps are not implemented.** `resort-online`
presence gives live online/offline only — nothing is persisted for "seen 2
hours ago." Ask if you need that; it's a small follow-up (needs a Pusher
webhook + one new column), not currently built.

## Mapping from the old Node service

| Old (`chat-backend`) | New | Notes |
|---|---|---|
| `POST /sync-user`, `/register-fcm`, `/unregister-fcm` | `POST add-device-token` / `POST remove-device-token` (already exists, not chat-specific) | Device token registration was never chat-specific — reuse the existing app-wide endpoints |
| `GET /rooms` | `GET chat/list` | |
| `GET /rooms/:roomId` | `GET chat/view/{type}/{type_id}` | |
| `POST /rooms` (create group) | `POST create/group-chat` | |
| `PUT /rooms/:roomId` (rename/update group) | **No equivalent yet.** | Not built — Laravel side has no group-update endpoint at all today. Flag if you need this. |
| `DELETE /rooms/:roomId` | `POST group/remove/{group_id}` | |
| `PUT /rooms/:roomId/add-member` | `POST group/add-member/{group_id}` | |
| `PUT /rooms/:roomId/remove-member` | `POST group/remove-member/{group_id}` | |
| `PUT /rooms/:roomId/read` | `GET chat/messages/mark-read?conversation_id=` | |
| `GET /rooms/:roomId/messages` | `GET chat/view/{type}/{type_id}` or `GET chat/get-messages/{type}/{type_id}` | |
| `POST /messages` (send) | `POST chat/send-message` | |
| `PUT /messages/:messageId/read` | `GET chat/messages/mark-read` | Old was per-message; new is per-thread — no single-message read receipt |
| `GET /users/line-workers` | `GET chat/start-new/chat` | New version is department-scoped (see table above); old was resort-wide |
| `GET /users/online/list` | `resort-online.{resortId}` presence channel | Now realtime/push-based, not a poll |
| Socket.io connection | Pusher private/presence channels (see above) | |

## Rollout

Both systems can run in parallel until the mobile app cuts over — nothing
here requires an immediate Node shutdown. Once the app is fully switched to
the endpoints/channels above, the Node `chat-backend` server can be
decommissioned; that's an infra step, not a code change.
