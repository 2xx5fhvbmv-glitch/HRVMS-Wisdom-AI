<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldTotalExpenceSinceJoining extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('total_expensess_since_joings', function (Blueprint $table) {
           $table->decimal('Total_Visa_Payment', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('total_expensess_since_joings', function (Blueprint $table) {
           $table->decimal('Total_Visa_Payment', 10, 2)->nullable();
        });
    }
}
