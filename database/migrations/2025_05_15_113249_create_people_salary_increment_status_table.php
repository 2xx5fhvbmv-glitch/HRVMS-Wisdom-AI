<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleSalaryIncrementStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people_salary_increment_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('people_salary_increment_id');
            $table->enum('approval_rank', ['Finance', 'GM']);
            $table->unsignedInteger('approved_by')->nullable();
            $table->enum('status',['Pending','Hold','Approved','Rejected','Change-Request'])->default('Pending');
            $table->string('remarks')->nullable();
            $table->string('reject_reason')->nullable();
            $table->date('action_date')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('modified_by')->nullable();
            $table->foreign('people_salary_increment_id', 'fk_salary_increment_id')->references('id')->on('people_salary_increment');
            $table->foreign('approved_by', 'fk_approved_by')->references('id')->on('employees')->onDelete('cascade');
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
        Schema::dropIfExists('people_salary_increment_status');
    }
}
