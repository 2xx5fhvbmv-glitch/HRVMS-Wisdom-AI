<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplinaryInvestigationChildrenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disciplinary_investigation_children', function (Blueprint $table) {
       
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedBigInteger('Disciplinary_P_id'); 
            $table->text('inves_find_recommendations');
            $table->string('follow_up_action')->nullable();
            $table->text('follow_up_description')->nullable();
            $table->text('investigation_stage')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->foreign('Disciplinary_P_id')->references('id')->on('grivance_investigation_parent_models')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disciplinary_investigation_children');
    }
}
