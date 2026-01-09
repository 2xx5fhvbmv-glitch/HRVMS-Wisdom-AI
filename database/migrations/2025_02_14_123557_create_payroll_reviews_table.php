<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_id');
            $table->unsignedInteger('employee_id');
            $table->string('Emp_id');
            $table->decimal('earnings_basic', 10, 2)->default(0);
            $table->decimal('earnings_allowance', 10, 2)->default(0);
            $table->decimal('earnings_normal', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payroll');
            $table->foreign('employee_id')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_reviews');
    }
}
