<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeReminderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_reminder', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->text('task');
            $table->string('days')->comment('Ex-5')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_reminder');
    }
}
