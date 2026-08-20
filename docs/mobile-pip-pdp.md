# PIP / PDP — Employee Mobile API

For the mobile team. Controller: `app/Http/Controllers/API/PipPdpController.php`.
Newly built — backs the "PIP and PDP listing screens" ask (Performance
Management, any rank — Line Worker through GM). Reads/writes the same
`employee_pip_plans` / `employee_pdp_plans` rows the web portal already uses
(`Resorts\Performance\PipPdpController`), so a plan created on the web shows
up here, and a response submitted here shows up there.

**Scope**: an employee's own plans only. The web controller also lets a
reporting manager or HR view/submit a report's plan on their behalf — that
oversight path is **not** built here, since the ask was specifically a
self-service listing screen. Flag if mobile also needs the manager-side
view.

PIP and PDP are two parallel, identically-shaped resources — same
endpoints, same response shape, just `pip` vs `pdp` in the URL.

## GET performance/my-pip-plans / GET performance/my-pdp-plans

Every plan assigned to the caller, most recent first.

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "duration": "30 Days",
      "factors": "Factor 1\r\nFactor 2\r\nFactor 3",
      "position": "Waitress",
      "template_name": "Performance Improvement Plan (PIP)",
      "status": "active",
      "submitted_at": null,
      "action_status": "Pending Action",
      "created_at": "2026-08-01T10:00:00.000000Z"
    }
  ]
}
```
`status` is `active` or `archived` (archiving is HR-only, web-side).
`action_status` is a convenience field — `"Pending Action"` until
`submitted_at` is set, then `"Submitted"` — same convention as the
`performance/my-reviews` listing.

## GET performance/pip-plan/{id} / GET performance/pdp-plan/{id}

Full detail: the plan's dynamic form template + any previously-saved
answers, for rendering the fill-in screen.

```json
{
  "success": true,
  "data": {
    "id": 1,
    "kind": "pip",
    "duration": "30 Days",
    "factors": "Factor 1\r\nFactor 2\r\nFactor 3",
    "position": "Waitress",
    "template_name": "Performance Improvement Plan (PIP)",
    "template": [
      { "type": "text", "name": "improvement_summary", "label": "Summary of Improvement Needed", "required": true },
      { "type": "file", "name": "supporting_document", "label": "Supporting Document", "required": false }
    ],
    "existing_data": { "improvement_summary": "..." },
    "status": "active",
    "can_edit": true,
    "submitted_at": null
  }
}
```
`template` is the same dynamic-form-builder structure used by every other
templated form in this app (Appraisal, Grievance investigation, etc.) —
render each entry by its `type` (`text`, `textarea`, `select`, `file`,
`ratingTable`, `header`, `paragraph`, ...). `existing_data` keys match each
field's `name`. `can_edit` is `false` once `submitted_at` is set — the plan
becomes read-only after submission, same as web.

404 if the plan doesn't exist or doesn't belong to the caller (no separate
403 — a plan that isn't yours doesn't leak its existence).

## POST performance/pip-plan/{id}/submit / POST performance/pdp-plan/{id}/submit

Submit responses. Send every non-file field as a normal form value keyed by
its `name`; send `file`-type fields as actual file uploads under the same
`name` (multipart). Only send fields belonging to this plan's own template.

```json
{ "success": true, "message": "PIP submitted successfully." }
```

Validation failures (missing required field):
```json
{ "success": false, "message": "Please fill all required fields", "errors": { "improvement_summary": "Summary of Improvement Needed is required." } }
```

`422` with `"This plan has already been submitted."` if you try to submit
twice — there's no re-edit/re-submit flow, matching web.

A `ratingTable`-type field is validated as "required" if **any** sub-key
prefixed `{field_name}_` has a non-empty value — send those as
`{field_name}_{row_key}` form fields, not a single JSON blob.

## GET performance/pip-plan/{id}/file/{field} / GET performance/pdp-plan/{id}/file/{field}

Downloads a previously-uploaded file for one `file`-type field on an
already-submitted plan (`field` = that field's `name`). `404` if the field
has no file on record or the plan isn't yours.

## Notifications

On submit: the plan's creator (whoever assigned it, resolved from
`created_by`) and the employee's reporting manager both get a push +
in-app notification — `"{Name} has submitted their {PIP|PDP} form."`,
module `Performance`, `pageId` `pip-pdp-submitted`.
