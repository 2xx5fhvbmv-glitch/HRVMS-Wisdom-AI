# Shopkeeper Module — Backend Follow-ups

For the backend developer. Found while restyling the three Shopkeeper
screens (Create, View all, Payments —
`app/Http/Controllers/Resorts/Payroll/ShopkeeperController.php`,
`resources/views/resorts/payroll/shopkeeper/*`). That work was
frontend/presentation-only per its own scope, so the two items below were
**flagged, not fixed** — they need a controller/model change, which is
outside what that pass touched.

---

## 1. Shopkeeper's hashed password is sent to the browser on every list load

`app/Models/Shopkeeper.php` has no `$hidden` property. The list screen's
AJAX endpoint (`ShopkeeperController::list()`) does:

```php
$tableData = Shopkeeper::where('resort_id', $resort_id)
    ->orderBy('updated_at', 'DESC')
    ->get();

return datatables()->of($tableData)-> ... ->make(true);
```

`->get()` with no `->select()` pulls every column, and yajra's
`datatables()->of()` serializes the full model — including the hashed
`password` column — into the JSON response sent to the browser for **every
row, on every page load** of `/resort/payroll/shopkeepers`. Confirmed by
reading the model and the query directly; not something I could verify
in a running browser since it's a response-body issue, not a rendering one.

Even hashed, a password should never leave the server. This is a real,
currently-live exposure — anyone with dev tools open on the Shopkeepers
list page can read every shopkeeper's password hash out of the network
tab, which is exactly the kind of thing that enables offline brute-forcing
if it's ever logged, cached, or intercepted.

**Fix** — either is fine, first is less code:

```php
// app/Models/Shopkeeper.php
protected $hidden = ['password'];
```

or scope the query itself in `list()`:

```php
$tableData = Shopkeeper::where('resort_id', $resort_id)
    ->orderBy('updated_at', 'DESC')
    ->select(['id', 'resort_id', 'name', 'email', 'contact_no', 'profile_photo', 'created_at', 'updated_at'])
    ->get();
```

`$hidden` is the safer of the two — it protects every future place this
model gets serialized (a new endpoint, an API resource, etc.), not just
this one query. `list()` is also called by the DataTables JS via
`row.profile_photo`/`row.name`/etc. on the frontend, so whichever fix you
pick, keep those columns selected/visible.

## 2. `inlineUpdate()` runs `ucwords()` on the email field

`ShopkeeperController::inlineUpdate()`:

```php
$shopkeeper->email = ucwords($request->input('email'));
```

`ucwords()` capitalizes the first letter of each "word" (space-delimited
by default) — for a typical email with no spaces, that means it just
capitalizes the very first character: `john@example.com` → `John@example.com`.
Emails should be stored case-preserved (or lowercased), never
title-cased — most mail providers treat the local part as
case-**insensitive** in practice, but this is still wrong and worth
cleaning up, and there's no reason the update path should transform the
value here at all.

**Fix:**

```php
$shopkeeper->email = trim($request->input('email'));
// or, if the app's convention elsewhere is to normalize emails to lowercase:
$shopkeeper->email = strtolower(trim($request->input('email')));
```

Worth checking `store()` too — it saves `$request->email` as-is with no
`ucwords()`, so `store()` and `inlineUpdate()` currently disagree on how a
shopkeeper's email ends up stored depending on whether it was set at
creation or edited afterward. Whatever you land on, make both paths do the
same thing.

---

## Context: what the frontend pass already fixed (no action needed)

Listed here only so nothing gets duplicated — these were bugs, but
presentation-layer ones the restyle pass was able to fix directly:

- `list()`'s `->escapeColumns(['action'])` meant "escape *this* column"
  (confirmed from yajra's source — the opposite of the intended "allow
  HTML" meaning), which would have broken the action-column buttons. Now
  `->escapeColumns([])`.
- The View-all DataTable's `order:[[6,'desc']]` pointed at a column index
  that never existed (only 6 columns were ever defined, now 5). Now
  `order:[[0,'asc']]`.
- A dead, unreachable second delete-confirmation block in `index.blade.php`
  referenced undefined variables (`shopkeeperId`, `action`) — removed as
  part of replacing the delete flow with an inline confirm.
- The old delete success handler read `result.msg`; the controller actually
  returns `message`. Fixed on the frontend side.
