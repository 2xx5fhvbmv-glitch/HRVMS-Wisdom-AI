<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinaryWitnessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinary_witnesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('Disciplinary_id');
            $table->integer('Employee_id');
            // $table->integer('Committee_id');
            $table->text('Statement')->nullable();
            $table->text('Attachement')->nullable();
            $table->enum('Request_For_Statement',['Yes','No'])->default('No');
            $table->enum('Wintness_Status', ['Requested', 'Approved', 'NoAction'])->default('Requested');
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
        Schema::dropIfExists('disciplinary_witnesses');
    }
}
