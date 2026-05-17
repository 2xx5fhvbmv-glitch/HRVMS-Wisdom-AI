<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Adds the "Document Management" page to the Visa module (Module_Id = 14)
 * submenu.
 *
 * The page + route + view already exist (resort.visa.DocumentManage →
 * Visa\DocumentController@index) but it was never registered in
 * `module_pages`, so it never appeared in the Visa dropdown — it was only
 * reachable by typing /resort/visa/document-management directly.
 *
 * The Visa submenu renders from `module_pages` (see Common::GetResortMenuPage)
 * and each item is permission-gated by Common::resortHasPermission(). After
 * this migration the item is visible immediately to super/master admins;
 * regular resort positions still need "Document Management" view access
 * granted through the existing Page Permission screen, the same way every
 * other Visa page is governed.
 *
 * It is slotted at place_order 11 (just before "Configuration", which is
 * bumped to 12 so it stays last).
 */
class AddDocumentManagementToVisaMenu extends Migration
{
    private const VISA_MODULE_ID = 14;
    private const ROUTE          = 'resort.visa.DocumentManage';
    private const CONFIG_ROUTE   = 'visa.config';

    public function up()
    {
        // Idempotency guard — don't double-insert if the migration is re-run.
        $exists = DB::table('module_pages')
            ->where('Module_Id', self::VISA_MODULE_ID)
            ->where('internal_route', self::ROUTE)
            ->exists();

        if ($exists) {
            return;
        }

        // Push "Configuration" down one slot so Document Management can sit
        // at 11 and Configuration remains the last item.
        DB::table('module_pages')
            ->where('Module_Id', self::VISA_MODULE_ID)
            ->where('internal_route', self::CONFIG_ROUTE)
            ->update(['place_order' => 12]);

        DB::table('module_pages')->insert([
            'Module_Id'      => self::VISA_MODULE_ID,
            'page_name'      => 'Document Management',
            'internal_route' => self::ROUTE,
            'TypeOfPage'     => 'InsideOfMenu',
            'type'           => 'normal',
            'status'         => 'Active',
            'place_order'    => 11,
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),
        ]);
    }

    public function down()
    {
        DB::table('module_pages')
            ->where('Module_Id', self::VISA_MODULE_ID)
            ->where('internal_route', self::ROUTE)
            ->delete();

        // Restore Configuration to its original slot.
        DB::table('module_pages')
            ->where('Module_Id', self::VISA_MODULE_ID)
            ->where('internal_route', self::CONFIG_ROUTE)
            ->update(['place_order' => 11]);
    }
}
