<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkPermitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();
              $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->string('Month')->nullable();
            $table->string('Currency')->nullable();
            $table->string('Amt')->nullable();
            $table->date('Payment_Date')->nullable();
            $table->date('Due_Date')->nullable();
            $table->enum('Status',['Paid','Unpaid'])->default('Unpaid');
            $table->string('Reciept_file')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
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
        Schema::dropIfExists('work_permits');
    }
}
