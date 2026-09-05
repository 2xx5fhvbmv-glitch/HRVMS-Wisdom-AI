# Disciplinary Investigation — File Uploads Bypass StorageHelper

**Status:** Root cause confirmed, not fixed (frontend-only pass — out of scope for this change).
**Scope:** Backend only — `app/Http/Controllers/Resorts/GrievanceAndDisciplinery/DisciplinaryController.php`, `InvestigationReportStore()`.

## The bug

Investigation file uploads write straight to the local filesystem instead of going through `StorageHelper`:

```php
// DisciplinaryController.php:631-634
$FilePath = config('settings.DisciplinaryAttachments')
    .'/'.$this->resort->resort->resort_id
    .'/'.$request->Disciplinary_form_id;
foreach($file as $f) {
    $f->move(public_path($FilePath), $f->getClientOriginalName());
    $Files[] = $f->getClientOriginalName();
}
```

`public_path()` + `->move()` is a direct violation of this codebase's hard invariant on `StorageHelper` (CLAUDE.md #2 — "the ONLY correct way to read/write uploaded files... anything not routed through `StorageHelper` works locally and silently breaks in prod"). The `.env` in this environment has `STORAGE_DRIVER=wasabi` (prod's real setting) — meaning any file uploaded through this form is written to the app server's local `public/` folder regardless of the configured driver, not to Wasabi. If prod ever runs multiple app instances/containers or gets redeployed, these files can silently vanish or be invisible to other instances.

## Why the frontend rewrite didn't fix the read side either

The Blade view (`investigationreport.blade.php`) reads these same files back with `URL::asset($Path . '/' . $filename)`, which **correctly matches how they're actually stored today** (a real local path under `public/`). Swapping the view to `StorageHelper::temporaryUrl()` — the pattern the Incident Investigation page uses correctly — would have **broken every existing download link**, since `StorageHelper` looks on the configured disk (Wasabi here), not `public/`. So the view was deliberately left on `URL::asset()`, matching the current (wrong) storage behavior, rather than "fixing" only one side of a two-sided bug.

## Suggested fix

1. In `InvestigationReportStore()`, replace the `public_path()`/`->move()` block with `StorageHelper::uploadFile($FilePath, $f)` (or equivalent), matching the pattern already used correctly elsewhere in this codebase (e.g. the Incident module).
2. Once uploads go through `StorageHelper`, update the view's file-link rendering (currently `URL::asset($Path . '/' . $filename)` in three places — main Attachments/Signed Document facts, and the History table's per-round attachment links) to use `StorageHelper::temporaryUrl($Path . '/' . $filename)` instead.
3. Do both together — fixing only the write side or only the read side will break the other.
4. Decide what to do with files already uploaded under the old (local `public/`) path before the fix ships, so existing case history doesn't lose its attachments.
