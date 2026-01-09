<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartureArrangementsColumnToEmployeeResignationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->json('departure_arrangements')->nullable()->after('resignation_date')->comment('Details of departure arrangements made by the employee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            //
        });
    }
}
