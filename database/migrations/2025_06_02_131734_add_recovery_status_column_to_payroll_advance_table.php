<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRecoveryStatusColumnToPayrollAdvanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_advance', function (Blueprint $table) {
            $table->enum('recovery_status', ['Pending', 'In Progress', 'Scheduled','Completed','Failed'])
                ->default('Pending')
                ->after('status')
                ->comment('Status of the recovery process');
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
            $table->dropColumn('recovery_status');
        });
    }
}
