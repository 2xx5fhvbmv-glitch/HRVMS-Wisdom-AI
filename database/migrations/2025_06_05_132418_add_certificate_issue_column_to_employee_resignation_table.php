<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateIssueColumnToEmployeeResignationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->enum('certificate_issue', ['yes', 'no'])->default('no')->after('resignation_date')->comment('resignation certificate has issued');
            $table->enum('full_and_final_settlement', ['yes', 'no'])->default('no')->after('certificate_issue')->comment('full and final settlement has been done');
           DB::statement("ALTER TABLE employee_resignation 
            MODIFY status ENUM('Pending', 'Approved','Completed','Rejected','On Hold') 
            NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_resignation', function (Blueprint $table) {
            $table->dropColumn('certificate_issue');
            $table->dropColumn('full_and_final_settlement');
            DB::statement("ALTER TABLE employee_resignation 
            MODIFY status ENUM('Pending', 'Approved','Rejected','On Hold') 
            NOT NULL DEFAULT 'Pending'");
        });
    }
}
