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
| GET | `chat/start-new/chat?search=` | Employee picker to start a new 1:1 chat — **unrestricted for everyone**, no department scoping. (Changed — see §Permission model below; this used to be department-scoped for non-HR/GM, that was wrong per the product spec and has been removed.) |
| GET | `group/new-group-member/list` | **New.** Candidate picker for the *create group* screen (no group exists yet) — scoped to what the caller is allowed to add: any department for HR/GM, own department only for other HOD/XCOM/Finance, 403 for anyone who can't create groups at all. Use this instead of a generic employee list so the UI never offers a choice the server will reject. |
| GET | `group/new-employee/list/{group_id}` | Employee picker for adding to an *existing* group — same permission scoping as above, now enforced (was resort-wide/unscoped before). |
| POST | `create/group-chat` | Create a group. Body: `name` (required), `description`, `members[]` (required, array of resort-admin ids). **Now permission-checked server-side** — see §Permission model. 403 if the caller can't create groups, or if any member id is outside their allowed scope (department mismatch, or a different resort entirely). |
| POST | `group/add-member/{group_id}` | Body: `members[]` (array of resort-admin ids). **Now requires the caller to be that group's Group Admin** (403 otherwise) and enforces the same member-scope rule as group creation. |
| POST | `group/remove-member/{group_id}` | Body: `member_id` (single id). **Now requires Group Admin** (403 otherwise). The group's `admin`-role member still cannot be removed. |
| POST | `group/remove/{group_id}` | Deletes the group (and its conversation history). **Now Group Admin** (creator, or HR override — see §Permission model), not strictly creator-only. |
| POST | `group/update/{group_id}` | **New.** Group Admin only. `multipart/form-data`: `name` (optional, renames), `photo` (optional image file, max 5MB, replaces the group photo). Returns `{"success":true,"group":{"id":12,"name":"...","profile_picture":"https://... or null"}}`. There was no way to rename a group or set a photo before this. |
| GET | `chat/view/{type}/{type_id}` | Conversation header (other person/group info) + full message history. `type` is `individual` or `group`; `type_id` is the other resort-admin's id or the group id. **Group response shape changed** — see below. Also: group message history was silently broken before (only ever returned messages *you* sent, never anyone else's in the group) — that's fixed now. |
| GET | `chat/get-messages/{type}/{type_id}` | Alias of `chat/view` — same response, same params. Use whichever reads better client-side. |
| POST | `chat/send-message` | Multipart. Fields: `type` (`individual`\|`group`, required), `type_id` (required), `message` (**now optional** if `attachment` is present — was hard-required before, so a photo with no caption used to fail validation), `attachment` (optional file, now validated server-side: `jpg,jpeg,png,pdf,doc,docx,xls,xlsx`, max 10MB) |
| GET | `chat/messages/mark-read?conversation_id=` | Marks that conversation thread read for you — **not** a single-message read receipt, it flips your `chat_message_read` row for the whole thread |

`chat/view`'s response for `type=group` now includes the member list and whether *you* can manage this group:
```json
{
  "success": true,
  "data": {
    "id": 12, "name": "F&B Operations", "profile": "https://... or a default group icon",
    "members": [
      {"id": 259, "name": "Olivia Davis", "profile": "https://...", "role": "admin"},
      {"id": 250, "name": "Fatima Naseer", "profile": "https://...", "role": "member"}
    ],
    "is_admin": true
  },
  "receiver_id": 12, "type": "group", "messages": [ ... ]
}
```
`members` and `is_admin` are new. Gate your manage UI (rename, add/remove member, delete, change photo) on `is_admin` — don't re-derive "am I allowed to manage this" client-side, since HR override (below) means the creator isn't always the only admin.

**Tenant isolation, newly enforced**: `chat/view` and `chat/send-message` both now 404/403 if the individual recipient or group belongs to a different resort, or if you're not that group's member (and not covered by HR override). Previously unchecked — a crafted `type_id` for another resort's user/group would have gone through.

## Permission model (server-enforced, not just a UI convention)

| Caller | Can create a group? | Member selection |
|---|---|---|
| GM (rank 8) | yes | any department |
| HR (rank 3) | yes | any department |
| EXCOM/HOD **inside the HR department** (rank 1/2) | yes | any department |
| Other EXCOM/HOD (rank 1/2), Finance approver (rank 7) | yes | **own department only** |
| Everyone else (line workers, etc.) | **no** | — |

One-to-one chat ignores all of this — always unrestricted, every rank, no exceptions.

**Group Admin.** The creator is a group's admin (`role: "admin"` in the members list). Separately: if a group was created by an HR user (rank 3, or rank 1/2 inside the HR department), *any* HR user — not only the creator — is also treated as admin for that group. This does not apply to non-HR-created groups; a Finance HOD's group stays admin-only to its actual creator even when an HR user looks at it. This is exactly what `is_admin` in `chat/view` reflects.

Member-scope is enforced server-side on both group creation and add-member — hand-crafting a request with an out-of-scope employee id doesn't bypass it, it 403s.

`chat/send-message` response:
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {"message_id": 123, "message": "hey"},
  "chat_history": [ {"id":1,"type":"individual","type_id":242,"sender_id":241,"message":"...","attachment":null,"created_at":"..."} ]
}
```

## Known bug in `chat/list` — `last_seen` is not the last-message time

`ChatController::index()` labels a field `last_seen` on every row (individual
at `app/Http/Controllers/API/ChatBoat/ChatController.php:78`, group at
`:116`), but it's actually `$ResortAdmin->updated_at` / `$group->updated_at`
— the other person/group record's own last-edited timestamp (profile edit,
rename, etc.), not when the last message in that thread was sent. A
colleague you haven't messaged in weeks but who recently touched their
profile shows as "recent"; a colleague you just messaged can show as
"15 days" if their record hasn't otherwise changed.

The real value is already being fetched and just not exposed: both branches
already query the actual last message into `$lastMessage` (used today only
for `$lastMessage->message`) — its `created_at` is sitting right there.
One added line per branch:

```php
'last_msg_time' => $lastMessage->created_at ?? null,
```

— right next to the existing `'last_msg' => $lastMessage->message ?? null,`
line in both the individual (`:78` area) and group (`:116` area) mappers.
Additive only — doesn't remove/rename `last_seen`, so nothing currently
reading that field breaks; it just also gets the field it should have had.

Once `last_msg_time` exists in the response, the web portal's floating chat
widget (`resources/views/resorts/partials/wisdom-chat.blade.php`,
`renderConversations()`) needs one matching change: swap
`timeAgo(c.last_seen)` for `timeAgo(c.last_msg_time)` for the row's
timestamp. I haven't made that change yet — flagging it here first since it
depends on this backend field existing. Mobile should do the equivalent
swap in whatever renders the `chat/list` row's timestamp today (currently
`last_seen`, per the same bug).

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

**This was broken for everyone, mobile included, until this week** — the
provider that loads `routes/channels.php` (where every `Broadcast::channel()`
rule below is defined — `chat.{id}`, `group.{id}`, `resort-online.{id}`) was
disabled in `config/app.php`, so none of those authorization rules existed
at runtime regardless of which auth endpoint you pointed at. Every private/
presence subscription 403'd unconditionally. That's fixed now (the provider
is enabled), independently of the `/api` vs session-guard endpoint distinction
above. If the mobile app has any fallback/compensating logic for realtime
never actually connecting (e.g. polling `chat/list` instead of trusting the
socket), it's worth re-testing whether that's still needed.

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
| `PUT /rooms/:roomId` (rename/update group) | `POST group/update/{group_id}` | Now built (was missing when this doc was first written) |
| `DELETE /rooms/:roomId` | `POST group/remove/{group_id}` | |
| `PUT /rooms/:roomId/add-member` | `POST group/add-member/{group_id}` | |
| `PUT /rooms/:roomId/remove-member` | `POST group/remove-member/{group_id}` | |
| `PUT /rooms/:roomId/read` | `GET chat/messages/mark-read?conversation_id=` | |
| `GET /rooms/:roomId/messages` | `GET chat/view/{type}/{type_id}` or `GET chat/get-messages/{type}/{type_id}` | |
| `POST /messages` (send) | `POST chat/send-message` | |
| `PUT /messages/:messageId/read` | `GET chat/messages/mark-read` | Old was per-message; new is per-thread — no single-message read receipt |
| `GET /users/line-workers` | `GET chat/start-new/chat` | Resort-wide, same as old — no department scoping |
| `GET /users/online/list` | `resort-online.{resortId}` presence channel | Now realtime/push-based, not a poll |
| Socket.io connection | Pusher private/presence channels (see above) | |

## Rollout

Both systems can run in parallel until the mobile app cuts over — nothing
here requires an immediate Node shutdown. Once the app is fully switched to
the endpoints/channels above, the Node `chat-backend` server can be
decommissioned; that's an infra step, not a code change.
