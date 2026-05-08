<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceAppealHearingsTable extends Migration
{
    public function up()
    {
        Schema::create('grievance_appeal_hearings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appeal_id');
            // A single appeal may need several hearings (initial, follow-up,
            // re-convene). Sequence number gives the UI a stable order.
            $table->unsignedSmallInteger('sequence_no')->default(1);
            $table->date('hearing_date')->nullable();
            $table->time('hearing_time')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', [
                'Scheduled',
                'Completed',
                'Cancelled',
                'Rescheduled',
            ])->default('Scheduled');
            // JSON list of {participant_id, role} so we don't need a join
            // table for a feature that's mostly read-only after the hearing.
            $table->json('participants')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();

            $table->foreign('appeal_id')
                ->references('id')->on('grievance_appeals')
                ->onDelete('cascade');
            $table->index(['appeal_id', 'status']);
            $table->index('hearing_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('grievance_appeal_hearings');
    }
}
