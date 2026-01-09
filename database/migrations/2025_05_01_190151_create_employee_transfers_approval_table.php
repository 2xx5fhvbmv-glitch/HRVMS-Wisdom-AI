<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTransfersApprovalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_transfers_approval', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->enum('status',['Pending', 'Approved', 'Rejected', 'On Hold'])->default('Pending');
            $table->unsignedInteger('approved_by');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('employee_transfers')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('employees')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_transfers_approval');
    }
}
