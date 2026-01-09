<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTAnotificationChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_anotification_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Parent_ta_id')->nullable();
            $table->enum('status',['Hold','Approved','Rejected','Active','Expired','ForwardedToNext'])->default('Active');
            $table->date('holding_date')->format('d/m/y')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('Parent_ta_id')->references('id')->on('t_anotification_parents');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_anotification_children');
    }
}
