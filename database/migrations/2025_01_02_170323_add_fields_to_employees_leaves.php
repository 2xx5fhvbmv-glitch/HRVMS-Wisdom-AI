<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEmployeesLeaves extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            $table->date('departure_date')->after('transportation')->nullable();
            $table->date('arrival_date')->after('departure_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            $table->dropColumn('departure_date');
            $table->dropColumn('arrival_date');
        });
    }
}
