<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDurationToEmployeesLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            $table->string('duration')->after('total_days');
            $table->string('from_time')->nullable()->after('duration');
            $table->string('to_time')->nullable()->after('from_time');
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
            $table->dropColumn('duration');
            $table->dropColumn('from_time');
            $table->dropColumn('to_time');
        });
    }
}
