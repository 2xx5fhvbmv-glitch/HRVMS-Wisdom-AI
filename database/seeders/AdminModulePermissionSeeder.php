<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class AdminModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr = [];

        for($i=1 ; $i<=59; $i++) {
            for($j=1 ; $j<=8; $j++) {
                $arr[] = ['module_id' => $i,'permission_id' => $j];
            }
        }

        DB::table('admin_module_permissions')->insert($arr);
    }
}
