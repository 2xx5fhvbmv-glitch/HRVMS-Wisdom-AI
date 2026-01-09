<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddworkPermitMedicalRenewalsFeild extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
           $table->decimal('Amt', 15, 2)->default(0.00)->after('medical_file');
        });
        Schema::table('work_permit_medical_renewal_children', function (Blueprint $table) 
        {
           $table->decimal('Amt', 15, 2)->default(0.00)->after('medical_file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_permit_medical_renewals', function (Blueprint $table) {
           $table->dropColumn('Amt', 15, 2)->default(0.00)   ;
        });
        Schema::table('work_permit_medical_renewal_children', function (Blueprint $table) 
        {
           $table->dropColumn('Amt', 15, 2)->default(0.00)   ;
        });
    }
}
