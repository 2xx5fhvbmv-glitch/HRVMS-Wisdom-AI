<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAttendancePeriodTypeToEmployeesEducationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_education', function (Blueprint $table) {
            $table->string('attendance_period')->nullable()->change();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_education', function (Blueprint $table) {
            $table->year('attendance_period')->nullable()->change();
            $table->dropColumn('attendance_period');
        });
    }
}
