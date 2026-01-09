<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusEnumInEmployeeTravelPassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            DB::statement("ALTER TABLE employee_travel_passes MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected', 'Cancel') NOT NULL DEFAULT 'Pending'");
        });

        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            DB::statement("ALTER TABLE employee_travel_pass_status MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected', 'Cancel') NOT NULL DEFAULT 'Pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            DB::statement("ALTER TABLE employee_travel_passes MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'");
        });

        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            DB::statement("ALTER TABLE employee_travel_pass_status MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending'");
        });
    }
}
