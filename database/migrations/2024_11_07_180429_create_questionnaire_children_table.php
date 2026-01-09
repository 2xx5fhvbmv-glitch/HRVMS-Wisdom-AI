<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionnaireChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questionnaire_children', function (Blueprint $table) {
            $table->id();


            $table->unsignedBigInteger( 'Q_Parent_id');


            $table->string('Question',255);
            $table->enum('questionType',['single','multiple','Radio'])->default('single');
            $table->json('options')->nullable();
            $table->string('ans')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();


            $table->foreign( 'Q_Parent_id')->references('id')->on('questionnaires');




        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questionnaire_children');
    }
}
