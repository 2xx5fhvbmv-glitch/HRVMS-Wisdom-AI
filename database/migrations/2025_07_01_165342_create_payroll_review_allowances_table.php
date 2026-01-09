<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollReviewAllowancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payroll_review_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('payroll_review_id'); // Link to payroll_reviews
            $table->string('allowance_type'); // e.g., 'phone', 'transport'
            $table->decimal('amount', 10, 2);
            $table->enum('amount_unit', ['MVR', 'USD'])->default('USD'); // Currency unit, default to MVR
            $table->timestamps();

            $table->foreign('payroll_review_id')
            ->references('id')->on('payroll_reviews')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_review_allowances');
    }
}
