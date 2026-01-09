<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('file_id'); 
            $table->integer('version_number');
            $table->string('file_path');
            $table->string('uploaded_by');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('file_id')->references('id')->on('child_file_management')->onDelete('cascade');
            $table->timestamps();

        });
    }
    public function down()
    {
        Schema::dropIfExists('file_versions');
    }
}
