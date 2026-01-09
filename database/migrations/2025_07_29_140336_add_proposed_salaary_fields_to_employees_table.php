<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProposedSalaaryFieldsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('proposed_salary', 10, 2)->nullable()->after('basic_salary_currency');
            $table->enum('proposed_salary_unit', ['MVR', 'USD'])->default('USD')->after('proposed_salary');
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
            $table->dropColumn('proposed_salary');
            $table->dropColumn('proposed_salary_unit');
        });
    }
}
