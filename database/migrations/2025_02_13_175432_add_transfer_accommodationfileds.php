<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransferAccommodationfileds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_accommodations', function (Blueprint $table) {
            $table->date('OldDate')->nullable();
            $table->date('NewdDate')->nullable();
            $table->integer('Emp_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_accommodations', function (Blueprint $table) {
            $table->dropColumn(['OldDate', 'NewdDate','Emp_id']);

        });
    }
}
