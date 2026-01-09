<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePassTypeFromEmployeeTravelPasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->dropColumn('pass_type');
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
            $table->string('pass_type')->nullable(); // Restore the column if rolled back
        });
    }
}
