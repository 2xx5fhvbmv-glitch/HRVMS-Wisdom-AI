<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin › Resorts › Permissions used to re-add some page grants on every save (ResortsController::updatePermissions,
 * fixed 2026-11-11), so one resort could hold the same (module, page) grant many times. Keep the oldest row of each;
 * no grant is added or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE r FROM resort_pagewise_permissions r
            JOIN resort_pagewise_permissions k
              ON k.resort_id = r.resort_id AND k.Module_id = r.Module_id AND k.page_permission_id = r.page_permission_id AND k.id < r.id');
    }

    public function down(): void
    {
        // The removed rows were exact copies; nothing to restore.
    }
};
