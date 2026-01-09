<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatesColumnToPayrollAdvanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->date('hr_action_date')
                ->nullable()
                ->after('hr_status');
            $table->date('finance_action_date')
                ->nullable()
                ->after('finance_status');
            $table->date('gm_action_date')
                ->nullable()
                ->after('gm_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->dropColumn('hr_action_date');
            $table->dropColumn('finance_action_date');
            $table->dropColumn('gm_action_date');
        });
    }
}
