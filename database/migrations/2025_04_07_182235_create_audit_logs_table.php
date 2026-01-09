<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('file_id'); 
            $table->string('TypeofAction');
            $table->string('file_path');
            $table->string('uploaded_by');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('file_id')->references('id')->on('child_file_management');

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
        Schema::dropIfExists('audit_logs');
    }
}
