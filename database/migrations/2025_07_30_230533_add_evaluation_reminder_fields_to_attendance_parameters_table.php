<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvaluationReminderFieldsToAttendanceParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_parameters', function (Blueprint $table) {
            $table->string('evaluation_reminder', 255)->nullable()->after('auto_notifications');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_parameters', function (Blueprint $table) {
            $table->dropColumn('evaluation_reminder');
        });
    }
}
