<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveGuarternameColumnFromPayrollAdvanceGuarantorTable extends Migration
{
    /**
     * Run the migrations.
     *
    * @return void
    */
    public function up()
    {
       Schema::table('payroll_advance_guarantor', function (Blueprint $table) {
          $table->dropColumn(['guarantor_name', 'guarantor_position', 'guarantor_department']);
       });
    }

    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
       Schema::table('payroll_advance_guarantor', function (Blueprint $table) {
          $table->string('guarantor_name');
          $table->string('guarantor_position');
          $table->string('guarantor_department');
       });
    }
}
