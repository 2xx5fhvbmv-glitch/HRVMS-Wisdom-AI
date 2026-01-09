<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBasicSalaryForEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('joining_date')->after('nationality')->nullable();
            $table->decimal('basic_salary', 15, 2)->after('joining_date')->nullable();
            $table->date('incremented_date')->after('basic_salary')->nullable();
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
            $table->dropColumn('joining_date');
            $table->dropColumn('basic_salary', 15, 2);
            $table->dropColumn('incremented_date');
        });
    }
}
