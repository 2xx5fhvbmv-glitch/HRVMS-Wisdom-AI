<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmployeeItinerariesMeetingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_itineraries_meeting', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['meeting_participant_id']);

            // Rename and modify column to string for comma-separated values
            $table->renameColumn('meeting_participant_id', 'meeting_participant_ids');
        });

        Schema::table('employee_itineraries_meeting', function (Blueprint $table) {
            $table->string('meeting_participant_ids')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_itineraries_meeting', function (Blueprint $table) {
            // Change back to integer
            $table->unsignedInteger('meeting_participant_id')->change();

            // Rename back to original
            $table->renameColumn('meeting_participant_ids', 'meeting_participant_id');

            // Re-add foreign key
            $table->foreign('meeting_participant_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');
        });
    }
}
