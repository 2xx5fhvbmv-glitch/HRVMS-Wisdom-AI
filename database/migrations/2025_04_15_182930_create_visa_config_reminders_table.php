<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisaConfigRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visa_config_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');

            $table->enum('Work_Permit_Fee_reminder',['Active','InActive'])->default('InActive');
            $table->string('Work_Permit_Fee')->default(null);
            $table->enum('Slot_Fee_reminder',['Active','InActive'])->default('InActive');
            $table->string('Slot_Fee')->default(null);

            $table->string('Insurance')->default(null);
            $table->enum('Insurance_reminder',['Active','InActive'])->default('InActive');

 
            $table->enum('Medical_reminder',['Active','InActive'])->default('InActive');
            $table->string('Medical')->default(null);

 
            $table->enum('Visa_reminder',['Active','InActive'])->default('InActive');
            $table->string('Visa')->default(null);


            $table->string('Passport')->default(null);
            $table->enum('Passport_reminder',['Active','InActive'])->default('InActive');


            $table->foreign('resort_id')->references('id')->on('resorts');
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
        Schema::dropIfExists('visa_config_reminders');
    }
}
