<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('current_position_id');
            $table->unsignedInteger('new_position_id')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('new_level')->nullable();
            $table->decimal('current_salary',10,2)->nullable();
            $table->decimal('salary_increment_percent', 10, 2)->nullable();
            $table->decimal('salary_increment_amount', 10, 2)->nullable();
            $table->decimal('new_salary',10,2)->nullable();
            $table->integer('updated_benefit_grid')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status',['Approved','Rejected','Pending','On Hold'])->default('Pending');
            $table->enum('letter_dispatched',['Yes','No'])->default('No');
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('current_position_id')->references('id')->on('resort_positions')->onDelete('cascade');
            $table->foreign('new_position_id')->references('id')->on('resort_positions')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_promotions');
    }
}
