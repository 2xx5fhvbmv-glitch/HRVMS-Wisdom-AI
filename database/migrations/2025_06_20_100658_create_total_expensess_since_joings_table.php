<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTotalExpensessSinceJoingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('total_expensess_since_joings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employees_id');
            $table->decimal('Deposit_Amt', 15, 2)->default(0.00);
            $table->decimal('Total_work_permit', 15, 2)->default(0.00);
            $table->decimal('Total_slot_Payment', 15, 2)->default(0.00);
            $table->decimal('Total_insurance_Payment', 15, 2)->default(0.00);
            $table->decimal('Total_Work_Permit_Medical_Payment', 15, 2)->default(0.00);
            $table->date('Date')->nullable();
            $table->string('Year')->nullable();
            $table->foreign('employees_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('total_expensess_since_joings');
    }
}
