<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add the Announcements page (route: people.announcements) to the People
 * module's sub-menu so it shows up in the People dropdown AND so the
 * page-permission system (Common::checkRouteWisePermission, the Roles &
 * Permissions admin page) has a row to grant view / create / edit / delete
 * against.
 *
 * Before this migration the route was implemented and reachable by direct
 * URL (https://thewisdom.io/resort/people/announcements?empId=...), but
 * not surfaced anywhere in the navigation and not assignable from the
 * permission grid. Both flow from the same module_pages row.
 *
 * Idempotent on both up and down so it's safe to re-run during deploys.
 */
return new class extends Migration
{
    private const MODULE_ID    = 4; // People
    private const ROUTE_NAME   = 'people.announcements';

    public function up(): void
    {
        // Skip if a row with this internal_route already exists for the
        // People module (live may have been hand-patched between releases).
        $exists = DB::table('module_pages')
            ->where('Module_Id', self::MODULE_ID)
            ->where('internal_route', self::ROUTE_NAME)
            ->exists();

        if ($exists) {
            return;
        }

        // Place after the current highest place_order in the People module
        // so the new entry lands at the bottom of the dropdown instead of
        // re-numbering existing rows (which would invalidate any
        // permission caches keyed on order).
        $maxOrder = (int) DB::table('module_pages')
            ->where('Module_Id', self::MODULE_ID)
            ->whereNull('deleted_at')
            ->max('place_order');

        DB::table('module_pages')->insert([
            'Module_Id'      => self::MODULE_ID,
            'page_name'      => 'Announcements',
            'internal_route' => self::ROUTE_NAME,
            'TypeOfPage'     => 'InsideOfMenu',
            'type'           => 'normal',
            'status'         => 'Active',
            'place_order'    => $maxOrder + 1,
            'created_by'     => 0,
            'modified_by'    => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        // Hard-delete the row this migration inserted. Any role-permission
        // rows that referenced the page id will be left dangling, but the
        // permission resolver tolerates orphaned permission rows (the join
        // against module_pages returns no row → no menu entry, no
        // permission granted) so this is safe.
        DB::table('module_pages')
            ->where('Module_Id', self::MODULE_ID)
            ->where('internal_route', self::ROUTE_NAME)
            ->delete();
    }
};
