<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsuranceChildfield extends Migration
{
    public function up()
    {
        Schema::table('employee_insurance_children', function (Blueprint $table) {
           $table->decimal('Premium', 15, 2)->default(0.00)->after('insurance_file');
        });
    }

    public function down()
    {
        Schema::table('employee_insurance_children', function (Blueprint $table) {
           $table->dropColumn('Premium', 15, 2)->default(0.00);
        });
    }
}
