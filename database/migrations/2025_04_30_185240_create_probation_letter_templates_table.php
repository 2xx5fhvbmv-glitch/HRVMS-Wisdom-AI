<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProbationLetterTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('probation_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->enum('type', ['success', 'fail']);
            $table->string('subject');
            $table->longText('content');
            $table->longText('placeholers');
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('probation_letter_templates');
    }
}
