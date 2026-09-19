<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Casual Payroll" (resort.casualPayroll.index) as a menu item
 * in the Payroll module, alongside "Run Pay Roll". The controller itself
 * redirects away when the resort's casual_payment_model isn't 'direct_pay'
 * (see CasualPayrollController::index()) — the menu link exists for every
 * resort, but the page is only actually usable once that's configured.
 */
return new class extends Migration
{
    public function up()
    {
        $moduleId = DB::table('modules')->where('module_name', 'Payroll')->value('id');
        if (!$moduleId) {
            return;
        }

        DB::table('module_pages')->updateOrInsert(
            ['internal_route' => 'resort.casualPayroll.index'],
            [
                'page_name' => 'Casual Payroll',
                'Module_Id' => $moduleId,
                'TypeOfPage' => 'InsideOfMenu',
                'type' => 'normal',
                'place_order' => 3,
                'status' => 'Active',
                'created_by' => 1,
                'modified_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down()
    {
        DB::table('module_pages')->where('internal_route', 'resort.casualPayroll.index')->delete();
    }
};
