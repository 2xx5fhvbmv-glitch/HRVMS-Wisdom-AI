<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChildTablefield extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
                $table->text('InTime_Location')->nullable();
                $table->text('OutTime_Location')->nullable();

         });
    }


    public function down()
    {
        Schema::table('child_attendaces', function (Blueprint $table) {
            $table->dropColumn('InTime_Location');
            $table->dropColumn('OutTime_Location');
        });
    }
}
