<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilemangementSystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filemangement_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Folder_unique_id');
            $table->integer('UnderON')->default(0);
            $table->string('Folder_Name');
            $table->enum('Folder_Type',['uncategorized','categorized'])->default('uncategorized');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
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
        Schema::dropIfExists('filemangement_systems');
    }
}
