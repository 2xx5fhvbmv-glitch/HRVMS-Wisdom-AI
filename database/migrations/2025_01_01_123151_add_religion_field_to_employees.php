<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReligionFieldToEmployees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('contract_end_date')->nullable()->after('probation_end_date');
            $table->enum('religion', ['0', '1'])->nullable()->comment('0 -> non-muslim, 1 -> muslim')->after('skill');
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
            $table->dropColumn('contract_end_date');
            $table->dropColumn('religion');
        });
    }
}
