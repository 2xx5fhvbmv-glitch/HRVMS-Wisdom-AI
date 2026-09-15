<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplacementFieldsToVacanciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            // Distinct from the existing employee/employee_type='Replacement'
            // pair (a separate, purely-cosmetic top-level type that already
            // has an existing — buggy, out of scope here — string-into-int
            // bug on the `employee` column). This is a real, functional
            // mechanism for Casual/Intern specifically: a genuine
            // replacement doesn't consume new approved headcount.
            $table->boolean('is_replacement')->default(false)->after('out_of_budget_comment');
            $table->unsignedBigInteger('replacement_employee_id')->nullable()->after('is_replacement');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['is_replacement', 'replacement_employee_id']);
        });
    }
}
