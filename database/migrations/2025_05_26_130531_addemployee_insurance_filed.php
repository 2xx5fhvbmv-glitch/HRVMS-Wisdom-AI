<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddemployeeInsuranceFiled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('employee_insurances', function (Blueprint $table) {
            $table->string('Currency')->nullable()->after('insurance_end_date');
            $table->string('Premium')->nullable()->after('Currency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('employee_insurances', function (Blueprint $table) {
            $table->dropColumn('Currency');
            $table->dropColumn('Premium');
        });
    }
}
