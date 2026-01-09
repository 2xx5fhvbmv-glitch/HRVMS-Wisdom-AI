<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildFileManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_file_management', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Parent_File_ID');
            $table->string('File_Name');
            $table->string('File_Type');
            $table->string('File_Size');
            $table->string('File_Path');
            $table->string('File_Extension');
            $table->string('File_Upload_By');
            $table->string('File_Upload_Date');
            $table->string('File_Upload_Time');
            $table->string('File_Upload_IP');
            $table->foreign( 'resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->foreign( 'Parent_File_ID')->references('id')->on('filemangement_systems')->onDelete('cascade');
            
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
        Schema::dropIfExists('child_file_management');
    }
}
