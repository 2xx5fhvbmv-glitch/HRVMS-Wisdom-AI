<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarkColumnToPayrollRecoveryScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_recovery_schedule', function (Blueprint $table) {
            $table->text('remark')
                ->nullable()
                ->after('repayment_date')
                ->comment('Remark for the recovery schedule');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_recovery_schedule', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
}
