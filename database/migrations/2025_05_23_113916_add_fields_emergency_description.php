<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsEmergencyDescription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sos_history', function (Blueprint $table) {
            $table->text('emergency_description')->nullable()->after('time');
            $table->string('latitude')->change();
            $table->string('longitude')->change();
            $table->unsignedInteger('sos_approved_by')->nullable()->after('emergency_description');
            $table->time('sos_approved_time')->nullable()->after('sos_approved_by');
            $table->string('employee_message', 255)->nullable()->after('sos_approved_time');
            $table->string('team_message', 255)->nullable()->after('employee_message');

            $table->foreign('sos_approved_by')->references('id')->on('employees')->onDelete('cascade');
        });

        Schema::table('sos_history_employee_status', function (Blueprint $table) {
            $table->string('mass_instruction')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('latitude')->nullable()->change();
            $table->string('longitude')->nullable()->change();
        });

        Schema::table('sos_team_member_activity', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('latitude')->nullable()->change();
            $table->string('longitude')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sos_history', function (Blueprint $table) {
            $table->dropColumn('emergency_description');
            $table->dropColumn('sos_approved_by');
            $table->dropColumn('employee_message');
            $table->dropColumn('team_message');
        });
    }
}
