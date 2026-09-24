<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Employer signatory (the resort's HR Director) is frozen at issue
        // time, same reasoning as the other snapshot columns on this table.
        Schema::table('job_description_employee_records', function (Blueprint $table) {
            $table->unsignedInteger('employer_signatory_employee_id')->nullable()->after('employer_type_of_work');
            $table->string('employer_signatory_name')->nullable()->after('employer_signatory_employee_id');
            $table->string('employer_signatory_designation')->nullable()->after('employer_signatory_name');
            $table->string('employer_signatory_id_card_number')->nullable()->after('employer_signatory_designation');
        });
    }

    public function down()
    {
        Schema::table('job_description_employee_records', function (Blueprint $table) {
            $table->dropColumn([
                'employer_signatory_employee_id',
                'employer_signatory_name',
                'employer_signatory_designation',
                'employer_signatory_id_card_number',
            ]);
        });
    }
};
