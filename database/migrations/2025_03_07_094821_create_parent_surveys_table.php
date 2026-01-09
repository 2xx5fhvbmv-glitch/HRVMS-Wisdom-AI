<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentSurveysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parent_surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Surevey_title')->nullable();
            $table->date('Start_date')->nullable();
            $table->date('End_date')->nullable();
            $table->enum('Recurring_survey',['Daily','Weekly','Monthly','Quarterly','Annually'])->default('Weekly');
            $table->integer('Reminder_notification')->default(7);
            $table->integer('Min_response')->default(1);
            $table->enum('Allow_edit',['yes','No'])->default('yes');
            $table->enum('Status',['Publish','SaveAsDraft'])->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parent_surveys');
    }
}
