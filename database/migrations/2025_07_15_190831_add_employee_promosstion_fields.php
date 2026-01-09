<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeePromosstionFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->unsignedBigInteger('Jd_id')->after('resort_id')->nullable();
            $table->foreign('Jd_id')->references('id')->on('job_descriptions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_promotions', function (Blueprint $table) {
            $table->dropForeign(['Jd_id']);
            $table->dropColumn('Jd_id');
        });
    }
}
