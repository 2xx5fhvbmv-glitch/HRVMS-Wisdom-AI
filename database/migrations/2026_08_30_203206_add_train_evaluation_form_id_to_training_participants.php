<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainEvaluationFormIdToTrainingParticipants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('training_participants', function (Blueprint $table) {
            $table->unsignedInteger('train_evaluation_form_id')->nullable()->after('train_feedback_form_id');
            $table->foreign('train_evaluation_form_id', 'fk_train_evaluation_form_id')->references('id')->on('evaluation_form')->onDelete('set null');
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
            $table->dropForeign('fk_train_evaluation_form_id');
            $table->dropColumn('train_evaluation_form_id');
        });
    }
}
