# File Management — Sharing & Notifications

For the mobile team. Covers two related tickets: incorrect files shown in
"Shared with Me", broken shared-folder navigation, and missing
share notifications.

## Ticket 1 — "Shared with Me shows files nobody shared with me"

Checked directly against the data: not a bug. The specific files reported
(Payroll Review, Brand Logo, HR Policy) genuinely have `file_shares` rows
with `scope_type = "organization"` (share-with-everyone), created via the
web portal's Share modal by a real admin, real timestamps. `GET
resort/filemanagement/shared-with-me` is correctly reflecting what's in the
database.

**Real gap found and fixed:** the web Share modal's "Entire Organization"
option had no confirmation step — one radio click shared an item with
every active employee at the resort permanently, same weight as picking a
single person. Added a confirmation dialog before any org-wide share
submits. The 3 already-existing org-wide shares were left in place (not
auto-revoked) — revoke manually via the Share modal's "Revoke" button on
the web portal if they were unintended.

## Ticket 2 — Navigation / folder access issues in Shared with Me

Two real backend bugs, both fixed:

1. **Depth limit** — `folderAccessible()` only checked a folder against the
   shared-folder list at itself or one level up, so any shared folder that
   itself had subfolders (2+ levels deep) 403'd. Now walks the full parent
   chain, any depth.
2. **No back-navigation field** — folder responses had no field at all for
   "what's the parent to go back to". Added `parent_unique_id` to every
   folder object:
   - `null` → top-level entry (own root, or a directly-shared root) — back
     should return to My Drive / Shared with Me, not fetch a folder that
     would 403.
   - otherwise → the real parent folder's `unique_id`, safe to fetch.

```json
{
  "id": 281, "unique_id": "9c192c5e2d", "name": "Company Policy",
  "is_own": false, "parent_unique_id": null,
  "file_count": 1
}
```

"My Drive becomes unresponsive after entering a Shared with Me folder" is
most likely client-side navigation-stack state — the fields above give
mobile what's needed to fix it, but that part isn't verifiable from the
backend.

## Ticket 3 — Employee not notified when a document is shared

`FileShareController::store()` had no notification of any kind before this
fix. Now sends a push + in-app notification to the actual new recipients
when a share is created:

- `employees` scope → only the newly-added `employee_ids` this call (not
  everyone already shared with — reopening the share modal and adding one
  more person doesn't re-notify the existing recipients).
- `departments` scope → active employees in the newly-added department(s).
- `organization` scope → all active employees, only the first time that
  exact item is shared org-wide (re-selecting "organization" for an
  already org-wide item is a no-op, not a re-notify).

```json
{
  "message": "Amey Tamshetti shared \"Screenshot 2026-05-06.pdf\" with you.",
  "module": "File Management",
  "type": "Document Shared",
  "request_id": 8
}
```
`request_id` is the `file_shares.id` row.

All three fixes verified against real data in rolled-back tests before
committing.
