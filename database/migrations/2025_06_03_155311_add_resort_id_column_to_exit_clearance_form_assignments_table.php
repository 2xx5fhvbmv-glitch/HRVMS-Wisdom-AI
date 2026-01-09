<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResortIdColumnToExitClearanceFormAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exit_clearance_form_assignments', function (Blueprint $table) {
            $table->unsignedInteger('resort_id')->after('id')->nullable();
            $table->unsignedInteger('department_id')->after('resort_id')->nullable();
            $table->integer('reminder_frequency')->after('department_id')->nullable();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('resort_departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exit_clearance_form_assignments', function (Blueprint $table) {
            $table->dropForeign(['resort_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn('resort_id');
            $table->dropColumn('department_id');
            $table->dropColumn('reminder_frequency');
        });
    }
}
