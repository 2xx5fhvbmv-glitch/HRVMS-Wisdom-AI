<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Housekeeping – Employee Task Flow & Completion Tracking (Trello).
 *
 * housekeeping_requests.employee_id is the room occupant, not a cleaner —
 * there was no column at all for "which employee is assigned to clean
 * this". Adds one, plus a comma-separated completion-photo column (same
 * convention as GrievanceSubmissionWitness::Attachement).
 *
 * Also renames the status enum to match the employee task flow the Trello
 * card asks for (Pending/Accepted/In-Progress/Completed/Not Completed) —
 * Approved/Rejected never fit an "accept a cleaning task" flow. Existing
 * rows are relabeled, not dropped: Approved -> Accepted, Rejected -> Not
 * Completed.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to_employee_id')->nullable()->after('employee_id');
            $table->text('photos')->nullable()->after('completed_at');

            $table->index(['resort_id', 'assigned_to_employee_id']);
        });

        DB::statement("ALTER TABLE housekeeping_requests MODIFY status ENUM('Pending','Approved','Rejected','In-Progress','Completed','Accepted','Not Completed') DEFAULT 'Pending'");
        DB::table('housekeeping_requests')->where('status', 'Approved')->update(['status' => 'Accepted']);
        DB::table('housekeeping_requests')->where('status', 'Rejected')->update(['status' => 'Not Completed']);
        DB::statement("ALTER TABLE housekeeping_requests MODIFY status ENUM('Pending','Accepted','In-Progress','Completed','Not Completed') DEFAULT 'Pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE housekeeping_requests MODIFY status ENUM('Pending','Approved','Rejected','In-Progress','Completed','Accepted','Not Completed') DEFAULT 'Pending'");
        DB::table('housekeeping_requests')->where('status', 'Accepted')->update(['status' => 'Approved']);
        DB::table('housekeeping_requests')->where('status', 'Not Completed')->update(['status' => 'Rejected']);
        DB::statement("ALTER TABLE housekeeping_requests MODIFY status ENUM('Pending','Approved','Rejected','In-Progress','Completed') DEFAULT 'Pending'");

        Schema::table('housekeeping_requests', function (Blueprint $table) {
            $table->dropIndex(['resort_id', 'assigned_to_employee_id']);
            $table->dropColumn(['assigned_to_employee_id', 'photos']);
        });
    }
};
