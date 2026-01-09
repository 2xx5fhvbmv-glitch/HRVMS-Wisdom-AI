<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('current_department_id');
            $table->unsignedInteger('target_department_id');
            $table->unsignedInteger('current_position_id');
            $table->unsignedInteger('target_position_id');
            $table->text('reason_for_transfer');
            $table->date('effective_date');
            $table->enum('transfer_status',['Permanent','Temporary'])->default('Permanent');
            $table->text('additional_notes')->nullable();
            $table->enum('status',['Pending', 'Approved', 'Rejected', 'On Hold'])->default('Pending');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->foreign('current_department_id')->references('id')->on('resort_departments')->onDelete('cascade');
            $table->foreign('target_department_id')->references('id')->on('resort_departments')->onDelete('cascade');

            $table->foreign('current_position_id')->references('id')->on('resort_positions')->onDelete('cascade');
            $table->foreign('target_position_id')->references('id')->on('resort_positions')->onDelete('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_transfers');
    }
}
