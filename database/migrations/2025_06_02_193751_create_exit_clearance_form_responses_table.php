<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExitClearanceFormResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exit_clearance_form_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->json('response_data')->nullable();
            $table->unsignedInteger('submitted_by')->nullable();
            $table->date('submitted_date')->nullable();
            $table->timestamps();

            $table->foreign('assignment_id')
                ->references('id')->on('exit_clearance_form_assignments')
                ->onDelete('cascade');
            $table->foreign('submitted_by')
                ->references('id')->on('employees')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exit_clearance_form_responses');
    }
}
