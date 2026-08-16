<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile onboarding needs the assigned pickup/medical-escort staff to
 * Accept/Reject/Complete their task (Assigned-Staff dashboard + the
 * itinerary "Upcoming Tasks"/"Assigned Tasks" sections) and the onboarding
 * employee's own itinerary screen to show a real checkmark/pending status
 * for those two items. No status tracking existed for these tasks at all
 * previously — they were pure read-only date/time rows.
 */
class AddTaskStatusToEmployeeItinerariesTable extends Migration
{
    public function up()
    {
        Schema::table('employee_itineraries', function (Blueprint $table) {
            $table->enum('pickup_status', ['Pending', 'Approved', 'Rejected', 'Completed'])
                ->default('Pending')->after('pickup_employee_id');
            $table->enum('medical_escort_status', ['Pending', 'Approved', 'Rejected', 'Completed'])
                ->default('Pending')->after('accompany_medical_employee_id');
        });
    }

    public function down()
    {
        Schema::table('employee_itineraries', function (Blueprint $table) {
            $table->dropColumn(['pickup_status', 'medical_escort_status']);
        });
    }
}
