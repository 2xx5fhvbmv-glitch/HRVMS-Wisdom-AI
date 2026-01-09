<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildMaintananceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_maintanance_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('maintanance_request_id');
            $table->string('ApprovedBy')->nullable();
            $table->integer('rank')->nullable();
            $table->date('date')->nullable();

            $table->enum('Status',['pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed'])->default('On-Hold');
            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('maintanance_request_id')->references('id')->on('maintanace_requests');
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
        Schema::dropIfExists('child_maintanance_requests');
    }
}
