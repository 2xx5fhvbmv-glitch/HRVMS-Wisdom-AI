<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers "Casuals — Payment Model" (people.casualPaymentModel.index) as
 * an InsideOfPage entry under People → Configuration, same tier as the
 * Exit Clearance / Resignation Reason / Increment Type sub-config pages.
 */
return new class extends Migration
{
    public function up()
    {
        $moduleId = DB::table('modules')->where('module_name', 'People')->value('id');
        if (!$moduleId) {
            return;
        }

        DB::table('module_pages')->updateOrInsert(
            ['internal_route' => 'people.casualPaymentModel.index'],
            [
                'page_name' => 'Casuals — Payment Model',
                'Module_Id' => $moduleId,
                'TypeOfPage' => 'InsideOfPage',
                'type' => 'normal',
                'place_order' => 0,
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
        DB::table('module_pages')->where('internal_route', 'people.casualPaymentModel.index')->delete();
    }
};
