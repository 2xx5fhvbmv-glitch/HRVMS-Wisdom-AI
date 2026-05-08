<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceAppealsTable extends Migration
{
    public function up()
    {
        Schema::create('grievance_appeals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            // The grievance the employee is appealing against. FK is loose
            // (no cascade) because the parent grievance can be archived /
            // soft-deleted independently of the appeal record we keep for
            // audit.
            $table->unsignedInteger('grievance_id');
            // Human-readable appeal id surfaced in the UI (e.g. APL-00001).
            $table->string('appeal_no', 32)->nullable()->unique();
            $table->unsignedInteger('submitted_by')->nullable(); // employee id
            $table->dateTime('submitted_at')->nullable();
            // Lifecycle: a hearing is scheduled → it runs → committee/GM
            // issues a decision → status flips to one of the terminal values.
            $table->enum('status', [
                'Pending',
                'In_Hearing',
                'Resolved',
                'Rejected',
                'Withdrawn',
            ])->default('Pending');
            // Decision rendered by the appeal panel. NULL while the appeal
            // is still in progress.
            $table->enum('decision', [
                'Upheld',      // original decision stands
                'Overturned',  // original decision reversed
                'Modified',    // partial — original decision changed
            ])->nullable();
            $table->dateTime('decision_at')->nullable();
            $table->unsignedInteger('decided_by')->nullable(); // admin id
            $table->text('reason')->nullable();          // why the appeal was filed
            $table->text('decision_notes')->nullable();  // free-form panel notes
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->index(['resort_id', 'status']);
            $table->index('grievance_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('grievance_appeals');
    }
}
