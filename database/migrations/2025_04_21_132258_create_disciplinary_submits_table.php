<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinarySubmitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinary_submits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Disciplinary_id')->unique();
            $table->integer('Employee_id');
            $table->integer('Committee_id');
            $table->integer('Category_id');
            $table->integer('SubCategory_id');
            $table->integer('Offence_id');
            $table->integer('Action_id');
            $table->integer('Severity_id');
            $table->string('Expiry_date')->nullable();
            $table->longText('Incident_description')->nullable();
            $table->text('Attachements')->nullable();
            $table->text('upload_signed_document')->nullable();
            $table->enum('Request_For_Statement',['Yes','No'])->default('No');
            $table->enum('Assigned',['Yes','No'])->default('No');
            $table->enum('select_witness',['Yes','No'])->default('No');
            $table->enum('SendtoHr',['Yes','No'])->default('No');
            $table->enum('status', ['pending', 'In_Review', 'resolved', 'rejected'])->default('pending');
            $table->enum('Priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable(); 
            $table->timestamps();
            $table->foreign('resort_id')->references('id')->on('resorts');  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disciplinary_submits');
    }
}
