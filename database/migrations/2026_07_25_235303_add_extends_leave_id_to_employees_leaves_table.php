<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtendsLeaveIdToEmployeesLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_leaves', function (Blueprint $table) {
            // Self-referencing: an "extend my leave" request is just a
            // normal new leave request (same approval chain, notifications,
            // balance logic) for the additional days, tagged back to the
            // original approved leave it continues from.
            $table->unsignedInteger('extends_leave_id')->nullable()->after('flag');
            $table->foreign('extends_leave_id')->references('id')->on('employees_leaves')->onDelete('set null');
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
            $table->dropForeign(['extends_leave_id']);
            $table->dropColumn('extends_leave_id');
        });
    }
}
