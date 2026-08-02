# Grievance Identity Disclosure — Request/Approve Flow

For the mobile team. Covers the new flow that lets a designated HR "key
person" ask a confidential-grievance submitter to reveal who they are, and
the submitter's approve/reject response.

## Background

A confidential grievance (`confidential` / `Grivance_Submission_Type == "Yes"`)
hides the submitter's identity from the web HR portal by default. Certain
employees configured on the resort's **Grievance & Disciplinary →
Configuration → Key Personnel for Identity Disclosure** page are allowed to
*request* to see who filed a specific confidential grievance — but only
after the submitter explicitly approves that specific request. Approval is
per-requester and permanent (they don't have to re-approve the same person
again), rejection just clears the request so it can be asked again later
(by the same or a different key person).

## What happens on the web side (nothing mobile needs to build)

A key person opens a confidential grievance's investigation page and clicks
**Request Identity Disclosure**. That fires:
`POST grievance-and-disciplinary/request-identity` (web-only route), which
sets the grievance's request state and sends this to your endpoints below.

## What mobile needs to build

### 1. Show the pending request

`GET resort/grievance/my-grievances` and `GET resort/grievance/{id}` now both
include an `identity_disclosure_request` field on grievances the
authenticated employee submitted:

```json
{
  "id": 41,
  "grievance_id": "GR-0003",
  "confidential": "Yes",
  "identity_disclosure_request": {
    "requested_by": "Priya Sharma"
  }
}
```

`identity_disclosure_request` is `null` when there's nothing pending. When
it's present, show a prompt: **"`{requested_by}` wants to know who submitted
this grievance — allow them to see your identity?"** with Yes/No.

### 2. Push notification

When a key person submits a request, the grievant's device receives a push
(module `Grievance Identity Disclosure Request`) with the same prompt text.
Tapping it should deep-link to that grievance's detail screen (`request_id`
in the payload is the grievance's `id`, same as used elsewhere — see
[`mobile-approval-flow-field.md`](mobile-approval-flow-field.md) for the
general payload shape) where the Yes/No prompt above is shown.

### 3. Respond

`POST resort/grievance/identity-disclosure-respond`

Body:
```json
{ "id": 41, "action": "approve" }
```
`action` is `"approve"` or `"reject"`. Only the grievance's own submitter can
respond (`created_by` must match the authenticated user), and only while a
request is actually pending — otherwise you'll get a 400/404 with a
`message` explaining why.

Success:
```json
{ "status": true, "message": "Response recorded successfully." }
```

On approve, the requesting key person's device gets a push (module
`Grievance Identity Disclosure Response`) confirming they can now see the
submitter's identity in the web portal — no mobile screen needed for that
side, it's HR-portal only.

## Things to know

- One request is in flight per grievance at a time — a key person can't
  send a second request while one is already pending.
- Rejecting doesn't block future requests; the same or another key person
  can request again later.
- This only applies to **confidential** (`Grivance_Submission_Type ==
  "Yes"`) grievances. Non-confidential ones already show the submitter's
  identity on the web portal with no request needed.
