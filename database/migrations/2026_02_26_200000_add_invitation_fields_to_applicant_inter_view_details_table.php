<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddInvitationFieldsToApplicantInterViewDetailsTable extends Migration
{
    public function up()
    {
        // Add new columns
        Schema::table('applicant_inter_view_details', function (Blueprint $table) {
            $table->string('invitation_token')->nullable()->unique()->after('EmailTemplateId');
            $table->unsignedInteger('interviewer_id')->nullable()->after('invitation_token');
            $table->text('rejection_reason')->nullable()->after('interviewer_id');

            $table->foreign('interviewer_id')->references('id')->on('resort_admins')->onDelete('set null');
        });

        // Update Status enum to include new values
        DB::statement("ALTER TABLE applicant_inter_view_details MODIFY COLUMN Status ENUM('Active','Slot Booked','Slot Not Booked','Invitation Sent','Invitation Rejected') DEFAULT 'Slot Not Booked'");
    }

    public function down()
    {
        // Revert Status enum
        DB::statement("ALTER TABLE applicant_inter_view_details MODIFY COLUMN Status ENUM('Active','Slot Booked','Slot Not Booked') DEFAULT 'Slot Not Booked'");

        Schema::table('applicant_inter_view_details', function (Blueprint $table) {
            $table->dropForeign(['interviewer_id']);
            $table->dropColumn(['invitation_token', 'interviewer_id', 'rejection_reason']);
        });
    }
}
