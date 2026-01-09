<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('Resort_id');
            $table->unsignedBigInteger('ta_child_id')->nullable();
            $table->string('link')->nullable();
            $table->date('link_Expiry_date')->nullable();
            $table->string('Old_ExpiryDate')->nullable();




            $table->timestamps();
            $table->foreign('Resort_id')->references('id')->on('resorts');
            $table->foreign('ta_child_id')->references('id')->on('t_anotification_children');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_links');
    }
}
