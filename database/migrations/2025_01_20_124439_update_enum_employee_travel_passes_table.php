<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEnumEmployeeTravelPassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            // Change the ENUM column to add the new value
            DB::statement("ALTER TABLE employee_travel_passes MODIFY COLUMN pass_type ENUM('Entry', 'Exit', 'Boarding') NOT NULL");
        });
    }

    public function down()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            // Revert the ENUM column to the previous state
            DB::statement("ALTER TABLE employee_travel_passes MODIFY COLUMN pass_type ENUM('Entry', 'Exit') NOT NULL");
        });
    }
}
