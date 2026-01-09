<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesLeavesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_leaves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('emp_id');
            $table->unsignedInteger('leave_category_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('total_days');
            $table->string('attachments')->nullable();
            $table->text('reason');
            $table->unsignedInteger('task_delegation');
            $table->string('destination')->nullable();
            $table->unsignedBigInteger('transportation')->nullable();
            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected',
            ])->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('emp_id')->references('id')->on('employees');
            $table->foreign('leave_category_id')->references('id')->on('leave_categories');
            $table->foreign('task_delegation')->references('id')->on('employees');
            $table->foreign('transportation')->references('id')->on('resort_transportations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees_leaves');
    }
}
