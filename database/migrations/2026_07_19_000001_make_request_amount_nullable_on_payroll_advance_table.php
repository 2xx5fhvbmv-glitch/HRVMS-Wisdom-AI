<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeRequestAmountNullableOnPayrollAdvanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Non-monetary request types (e.g. Employment Verification Letter)
        // have no amount, so the column must allow NULL.
        DB::statement("ALTER TABLE payroll_advance MODIFY request_amount DECIMAL(12,2) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE payroll_advance MODIFY request_amount DECIMAL(12,2) NOT NULL");
    }
}
