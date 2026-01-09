<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesLanguageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_language', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->string('language')->nullable();
            $table->enum('proficiency_level',['Beginner','Intermediate','Advanced','Fluent','Native'])->default('Beginner');
            $table->timestamps();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees_language');
    }
}
