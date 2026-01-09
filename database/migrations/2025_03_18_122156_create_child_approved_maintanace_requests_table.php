<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildApprovedMaintanaceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_approved_maintanace_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('child_maintanance_request_id');
            $table->unsignedBigInteger('maintanance_request_id');
            $table->string('ApprovedBy')->nullable();
            $table->integer('rank')->nullable();
            $table->date('date')->nullable();
            $table->enum('Status',['pending','On-Hold','Open','Assinged','In-Progress','Resolvedawaiting','Closed','Approved','Rejected'])->default('Resolvedawaiting');
            
            // Define foreign keys with shorter names
            $table->foreign('resort_id', 'fk_resort_id')->references('id')->on('resorts');
            $table->foreign('child_maintanance_request_id', 'fk_child_request_id')->references('id')->on('child_maintanance_requests');
            $table->foreign('maintanance_request_id', 'fk_main_request_id')->references('id')->on('maintanace_requests');

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
        Schema::dropIfExists('child_approved_maintanace_requests');
    }
}
