<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('resort_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_payroll', 10, 2);
            $table->integer('total_employees');
            $table->date('draft_date');
            $table->date('payment_date');
            $table->string('city_ledger_file')->nullable();
            $table->string('payroll_unit')->nullable()->default('$');
            $table->enum('status', ['draft', 'locked'])->default('draft');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll');
    }
}
