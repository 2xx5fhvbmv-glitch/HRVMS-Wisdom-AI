<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainFeedbackFormIdToTrainingParticipants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('training_participants', function (Blueprint $table) {
           $table->unsignedInteger('train_feedback_form_id')->nullable()->after('employee_id');
           $table->foreign('train_feedback_form_id', 'fk_train_feedback_form_id')->references('id')->on('training_feedback_form')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('training_participants', function (Blueprint $table) {
            $table->dropForeign('fk_train_feedback_form_id');
            $table->dropColumn('train_feedback_form_id');
        });
    }
}
