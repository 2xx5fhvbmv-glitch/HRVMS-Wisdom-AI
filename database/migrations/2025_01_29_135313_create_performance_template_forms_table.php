<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceTemplateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_template_forms', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('resort_id');
            $table->string('FormName')->nullable();
            $table->integer('Department_id')->nullable();
            $table->integer('Division_id')->nullable();
            $table->integer('Section_id')->nullable();
            $table->integer('Position_id')->nullable();
            $table->longtext('form_structure')->nullable();

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
        Schema::dropIfExists('performance_template_forms');
    }
}
