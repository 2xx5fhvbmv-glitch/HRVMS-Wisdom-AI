<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReasonTransportModeFieldToEmployeeTravelPasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->string('departure_mode')->nullable()->after('departure_time');
            $table->string('departure_reason')->nullable()->after('departure_mode');
            $table->string('arrival_mode')->nullable()->after('arrival_time');
            $table->string('arrival_reason')->nullable()->after('arrival_mode');
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
            $table->dropColumn('departure_mode');
            $table->dropColumn('departure_reason');
            $table->dropColumn('arrival_mode');
            $table->dropColumn('arrival_reason');
        });
    }
}
