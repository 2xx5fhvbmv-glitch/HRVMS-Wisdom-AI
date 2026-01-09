<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEarningFieldsToPayrollReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_reviews', function (Blueprint $table) {
            $table->dropColumn('earnings_normal'); // Remove old earnings_basic column
            $table->decimal('service_charge', 10, 2)->default(0)->after('Emp_id');
            $table->decimal('regularOTPay', 10, 2)->default(0)->after('service_charge');
            $table->decimal('holidayOTPay', 10, 2)->default(0)->after('regularOTPay');
            $table->decimal('earned_salary', 10, 2)->default(0)->after('earnings_basic');
            $table->decimal('earnings_overtime', 10, 2)->default(0)->after('earned_salary');
            $table->decimal('total_earnings', 10, 2)->default(0)->after('earnings_allowance');
            $table->decimal('total_deductions', 10, 2)->default(0)->after('total_earnings');
            $table->decimal('net_salary', 10, 2)->default(0)->after('total_deductions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'service_charge',
                'regularOTPay',
                'holidayOTPay',
                'earned_salary',
                'earnings_overtime',
                'total_earnings',
                'total_deductions',
                'net_salary'
            ]);
            $table->decimal('earnings_normal', 10, 2)->default(0)->after('earnings_allowance');

        });
    }
}
