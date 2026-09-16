<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per HOD-review round on a Monthly Check-In (Area of Improvement
 * comment -> employee Acknowledge/Decline). Insert-per-round, never
 * updated/overwritten once the employee responds, mirroring the
 * grivance_investigation_child_models / child_maintanance_requests
 * insert-per-round pattern already used elsewhere for approval history —
 * this table has no equivalent precedent of its own, since nothing else in
 * this codebase versions a resubmittable review cycle.
 */
class CreateMonthlyCheckinReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('monthly_checkin_reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('monthly_checkin_id');
            $table->unsignedInteger('round');

            // Snapshotted at initiation time so a later re-initiation with
            // different wording doesn't rewrite what an earlier round said.
            $table->text('area_of_improvement');
            $table->text('hod_comment')->nullable();
            $table->unsignedInteger('initiated_by'); // resort_admins.id, same convention as monthly_checking_models.created_by
            $table->timestamp('initiated_at')->nullable();

            $table->text('employee_comment')->nullable();
            $table->enum('employee_response', ['Pending', 'Acknowledged', 'Declined'])->default('Pending');
            $table->text('decline_reason')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('monthly_checkin_id')->references('id')->on('monthly_checking_models')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_checkin_reviews');
    }
}
