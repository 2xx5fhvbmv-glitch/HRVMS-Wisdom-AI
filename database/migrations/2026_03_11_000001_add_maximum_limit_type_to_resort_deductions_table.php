<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaximumLimitTypeToResortDeductionsTable extends Migration
{
    public function up()
    {
        Schema::table('resort_deductions', function (Blueprint $table) {
            $table->enum('maximum_limit_type', ['percentage', 'fixed'])->default('percentage')->after('maximum_limit');
        });
    }

    public function down()
    {
        Schema::table('resort_deductions', function (Blueprint $table) {
            $table->dropColumn('maximum_limit_type');
        });
    }
}
