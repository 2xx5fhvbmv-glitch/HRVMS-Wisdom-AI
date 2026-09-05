# File Management — "Create Folder" Silently Doesn't Save

**Status:** Root cause confirmed, not fixed (frontend-only pass — out of scope for this change).
**Scope:** Backend only — `app/Http/Controllers/Resorts/FileManagment/FileManageController.php`.
**Impact:** Every "Add Folder" action in File Management — HR dashboard, Admin dashboard, and any other caller of this endpoint — reports success but never persists the folder.

## The bug

`CreateFolder()` opens two nested transactions but only ever commits the inner one:

```php
// FileManageController.php:133
DB::beginTransaction();               // outer — level 1
try {
    $uniqueString = substr(md5(uniqid($request->Folder_Name, true)), 0, 10);

    DB::beginTransaction();           // FileManageController.php:137 — inner — level 2
    try {
        $filesystem = FilemangementSystem::updateOrCreate(["id" => $id], [
            'resort_id'        => $resortId,
            'Folder_Name'      => $Folder_Name,
            'Folder_unique_id' => $uniqueString,
            'UnderON'          => $UnderON,
            'Folder_Type'      => 'uncategorized',
        ]);
        // ... build $folderPath ...
        StorageHelper::disk()->put($folderPath, '');
        DB::commit();                 // FileManageController.php:159 — only closes level 2 → level 1
    }
    catch (S3Exception $e) { DB::rollBack(); /* ... */ }   // line 164
    catch (Exception $e)   { DB::rollBack(); /* ... */ }   // line 172

    // ... build the folder-list HTML for the response ...
    return response()->json(['success' => true, 'message' => $msg, 'data' => $string], 200); // line 208
}
catch (\Exception $e) { /* ... */ }
```

Laravel's `DB::commit()` decrements an internal transaction-depth counter and only issues the real `COMMIT` to the database when that counter reaches 0. Here it starts at 2 (two `beginTransaction()` calls), the one `DB::commit()` on line 159 brings it to 1, and **nothing ever brings it to 0** — there is no second `DB::commit()` for the outer transaction opened on line 133. The method returns its success JSON with the outer transaction still open.

When the HTTP request ends, that connection is torn down (or returned to the pool) with an uncommitted transaction still pending, so MySQL discards it. The `updateOrCreate()` row and the `StorageHelper::disk()->put()` call executed inside that transaction are both rolled back — even though the controller already told the client `"success": true` and handed back what looks like a fresh folder-list HTML fragment.

## How this was found / verified

Confirmed live, not just by reading the code:

1. Submitted a folder named `Ponytail_Verify_Folder` through the "Add Folder" UI.
2. UI showed the success toast: *"Success — folder created successfully."*
3. Queried the database directly (`FilemangementSystem::where('Folder_Name', 'like', '%Ponytail%')`) — **zero rows**, before or after the submit.
4. Confirmed the most recent row in the whole `filemangement_systems` table predated the test by over a week — nothing from the test request landed at all.

This is not new — the "Add Folder" UI was reworked on the frontend earlier in this session (twice), but both the old and new UI post to this exact same endpoint with the same params, so this has been broken independent of any frontend change. Anyone who has ever clicked "Add Folder" on the File Management HR or Admin dashboard has seen a false success message.

## Suggested fix

Pick one:

1. **Drop the redundant inner `DB::beginTransaction()`** (line 137) and its paired `DB::commit()` (line 159) — the outer transaction already covers the same code, and the two `catch` blocks' `DB::rollBack()` calls (lines 164, 172) still work correctly against a single-level transaction.
2. **Or keep the nesting but add the missing outer `DB::commit()`** right before the `return` on line 208 (and make sure every early-return/exception path in between also resolves the outer transaction — the inner catches currently only roll back one level).

Option 1 is the smaller, safer diff — there's no actual need for two transaction levels here; the inner one wraps the exact same DB write plus a storage write, with the outer's own `try/catch` never doing anything transaction-related of its own.

## Also worth checking

The `if(!isset($id))` branch at the top of the method (line 47) is dead code — `$id` is always assigned via `$id = isset($request->id) ? base64_decode($request->id) : 0;` a few lines above, so it is never actually unset, and the method always falls into the `else` (rename-style) validation branch regardless of whether this is a genuinely new folder. It happens to behave correctly by accident (`Rule::unique()->ignore(0)` doesn't exclude any real row), but it's worth a look while you're in this method.
