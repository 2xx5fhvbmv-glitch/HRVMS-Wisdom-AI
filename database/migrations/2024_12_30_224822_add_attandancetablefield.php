<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttandancetablefield extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->text('note')->nullable();
            $table->enum('OTStatus',["Approved","Rejected"])->nullable();

         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parent_attendaces', function (Blueprint $table) {
            $table->dropColumn('note');
            $table->dropColumn('OTStatus');
        });
    }
}
