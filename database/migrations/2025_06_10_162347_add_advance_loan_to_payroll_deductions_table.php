<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvanceLoanToPayrollDeductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->decimal('advance_loan', 10, 2)
                  ->default(0.00)
                  ->after('staff_shop')
                  ->comment('Advance loan/salary amount deducted from the employee\'s payroll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_deductions', function (Blueprint $table) {
            $table->dropColumn('advance_loan');
        });
    }
}
