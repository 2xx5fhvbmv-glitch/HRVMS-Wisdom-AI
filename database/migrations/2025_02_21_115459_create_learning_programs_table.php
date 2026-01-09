<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_programs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->string('name');
            $table->text('description');
            $table->string('objectives');
            $table->unsignedInteger('learning_category_id');
            $table->enum('audience_type',['departments', 'grades', 'employees'])->nullable();
            $table->json('target_audience'); // Store multiple selections
            $table->float('hours');
            $table->integer('days');
            $table->string('frequency');
            $table->string('delivery_mode');
            $table->unsignedInteger('trainer');
            $table->string('prior_qualification')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('learning_category_id')->references('id')->on('learning_categories')->onDelete('cascade');
            $table->foreign('trainer')->references('id')->on('employees')->onDelete('cascade');



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_programs');
    }
}
