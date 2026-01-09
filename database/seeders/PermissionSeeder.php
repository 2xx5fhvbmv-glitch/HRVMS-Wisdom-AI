<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createMultiplePermission = [
            ['name' => 'Create','order' => '2'],
            ['name' => 'Edit','order' => '3'],
            ['name' => 'Delete','order' => '4'],
            ['name' => 'View','order' => '1'],
            ['name' => 'Import','order' => '5'],
            ['name' => 'Export','order' => '6'],
            ['name' => 'History','order' => '7'],
            ['name' => 'Details','order' => '8'],
        ];

        DB::table('permissions')->insert($createMultiplePermission);

    }
}
