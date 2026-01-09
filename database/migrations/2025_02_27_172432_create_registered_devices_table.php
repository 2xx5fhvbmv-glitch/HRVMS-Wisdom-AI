<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegisteredDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('registered_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('emp_id');
            $table->string('device_id')->unique();
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('employees');        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('registered_devices');
    }
}
