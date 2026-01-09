<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceAppealDeadlineModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grievance_appeal_deadline_models', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('resort_id');
            $table->string('AppealDeadLine')->nullable();
            $table->integer('MemberId_or_CommitteeId')->nullable();
            $table->enum('Appeal_Type',['Committee','Individual'])->nullable();
            $table->enum('Proccess',['on','off'])->nullable();

            $table->date('date')->nullable();
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
        Schema::dropIfExists('grievance_appeal_deadline_models');
    }
}
