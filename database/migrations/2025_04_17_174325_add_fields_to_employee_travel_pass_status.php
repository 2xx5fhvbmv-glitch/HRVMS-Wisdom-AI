<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEmployeeTravelPassStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            $table->enum('emergency_cancel_status', ['Cancel'])->nullable()->after('status'); 
        });

        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->string('employee_departure_status')->nullable()->after('status');
            $table->string('employee_arrival_status')->nullable()->after('employee_departure_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_travel_pass_status', function (Blueprint $table) {
            $table->dropColumn('emergency_cancel_status');
        });

        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->dropColumn('employee_departure_status');
            $table->dropColumn('employee_arrival_status');
        });
    }
}
