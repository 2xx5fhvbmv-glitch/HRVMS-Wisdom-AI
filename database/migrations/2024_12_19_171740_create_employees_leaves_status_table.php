<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesLeavesStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_leaves_status', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('leave_request_id');
            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected',
            ])->nullable();
            $table->string('approver_rank')->nullable();
            $table->string('approver_id')->nullable();
            $table->date('approved_at');
            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('employees_leaves');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees_leaves_status');
    }
}
