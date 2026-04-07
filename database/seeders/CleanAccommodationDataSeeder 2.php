<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanAccommodationDataSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('assing_accommodations')->truncate();
        DB::table('available_accommodation_inv_items')->truncate();
        DB::table('available_accommodation_models')->truncate();
        DB::table('inventory_modules')->truncate();
        DB::table('inventory_category_models')->truncate();

        if (Schema::hasTable('transfer_accommodations')) {
            DB::table('transfer_accommodations')->truncate();
        }

        if (Schema::hasTable('occupancy_levels_hit_a_critical_thresholds')) {
            DB::table('occupancy_levels_hit_a_critical_thresholds')->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
