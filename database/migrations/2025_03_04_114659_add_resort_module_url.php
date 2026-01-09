<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResortModuleUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('module_pages', function (Blueprint $table) {
            $table->string('internal_route')->nullable();
            $table->enum('type',['para','normal'])->default('normal');
            $table->enum('TypeOfPage',['InsideOfPage','InsideOfMenu'])->default('InsideOfMenu');
            
        });
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('module_pages', function (Blueprint $table) {
            $table->dropColumn(['internal_route','type','TypeOfPage']);
        });
    }
}
