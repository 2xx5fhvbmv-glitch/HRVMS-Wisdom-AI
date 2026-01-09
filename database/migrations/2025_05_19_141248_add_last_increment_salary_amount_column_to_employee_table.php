<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastIncrementSalaryAmountColumnToEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('last_increment_salary_amount',5,2)->nullable()->after('incremented_date');
            $table->string('last_salary_increment_type')->nullable()->comment('Increment Type is like Annual etc.')->after('last_increment_salary_amount');
            $table->string('notes')->nullable()->comment('remark if any')->after('last_salary_increment_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('last_increment_salary_amount');
            $table->dropColumn('last_salary_increment_type');
            $table->dropColumn('notes');
        });
    }
}
