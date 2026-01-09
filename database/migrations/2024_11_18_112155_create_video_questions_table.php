<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_questions', function (Blueprint $table) {
            $table->id();


            $table->unsignedBigInteger( 'Q_Parent_id');


            $table->unsignedBigInteger('lang_id');
            $table->string('VideoQuestion',250)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->foreign( 'lang_id')->references('id')->on('resort_languages');
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
        Schema::dropIfExists('video_questions');
    }
}
