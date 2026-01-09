<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use DB;

class AdminModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createMultipleModules = [
            ['name' => 'Admin users', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Settings', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Roles Permissions', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Email Templates', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Sizes', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Brand Groups', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Brands', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Patterns', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing AUFRs', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Grades', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Production Cell', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Compound Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Country Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Failure Modes Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Product Groups Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Trailer Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Registration', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Inspection Details Correction', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Enquiry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Purchase Price Entry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Purchase Price Approval', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Moving Casing Record', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Shearography Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Weekly Planning Load', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'WIP Task Configuration', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Machine Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Buffing Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Blank Count Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Compound Standard Weights', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Casing Compound Standard Weights', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Compound Usage Report', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Permitted Elapsed Time', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Machine Plan Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Machine Plan Import', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Machine Plan Enquiry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Machine Plan Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Lightscribe Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Enquiry By Barcode', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'WIP Enquiry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Operator Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Route Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Curing Matrix Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Building Matrix Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Buffing Matrix Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Task Processing Hand Held', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Task Processing Touch Screen', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Shift Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Weekly Plan Enquiry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Operator Enquiry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Matrix X Reference Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Operator Weekly Report', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Inspector Initials Maintenance', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Weekly Plan Rollover', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'FIP Budget Entry', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Open Trailor', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Hotcure Quarantine Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Procure Quarantine Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'SUO/FUO Quarantine Parameters', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Procure Tread App Settings', 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s')],
        ];

        DB::table('admin_modules')->insert($createMultipleModules);
    }
}
