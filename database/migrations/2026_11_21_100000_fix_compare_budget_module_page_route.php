<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Audit run-3 §1: the seeder stored the Compare Budget page as "resort.budget.comparebudget,{id]" (typo), which matches
 * no route name, so nothing could ever check permission against it. Point it at the real route name.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_pages')->where('internal_route', 'resort.budget.comparebudget,{id]')
            ->update(['internal_route' => 'resort.budget.comparebudget', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // not restored: the old value was a typo
    }
};
