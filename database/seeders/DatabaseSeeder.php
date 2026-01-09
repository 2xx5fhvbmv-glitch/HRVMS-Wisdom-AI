<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\MasterModuleDataSeeder;
use Database\Seeders\SuperAdminAndSettingsSeeder;
use Database\Seeders\ResortModuleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            MasterModuleDataSeeder::class,
            SuperAdminAndSettingsSeeder::class,
            ResortModuleSeeder::class,
        ]);
    }
}
