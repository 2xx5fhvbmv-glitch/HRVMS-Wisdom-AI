<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApplicantFormFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->integer('NotiesPeriod');
            $table->enum('employment_status',['Available','Working'])->nullable();
            $table->float('SalaryExpectation')->nullable();
            $table->string('Total_Experiance')->nullable();
            // $table->string('notes')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn('NotiesPeriod');
            $table->dropColumn('employment_status',['Available','Working'])->nullable();
            $table->dropColumn('SalaryExpectation')->nullable();
            $table->dropColumn('Total_Experiance')->nullable();

            $table->dropColumn('notes')->nullable();

        });
    }
}
