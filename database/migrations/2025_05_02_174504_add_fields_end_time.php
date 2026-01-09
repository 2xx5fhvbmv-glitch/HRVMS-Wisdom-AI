<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsEndTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
     
            if (Schema::hasColumn('monthly_checking_models', 'time_of_discussion')) 
            {
                $table->renameColumn('time_of_discussion', 'start_time');
            }
            $table->string('end_time')->after('time_of_discussion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_checking_models', function (Blueprint $table) {
           
          $table->renameColumn( 'start_time','time_of_discussion',);
          $table->dropColumn('end_time');
        });
    }
}
