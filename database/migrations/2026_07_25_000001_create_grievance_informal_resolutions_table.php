<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceInformalResolutionsTable extends Migration
{
    public function up()
    {
        Schema::create('grievance_informal_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            // The mobile pre-check dialog shown before an employee starts a
            // formal grievance: "did you try to resolve this informally
            // first?" — logged even when the answer is Yes and the employee
            // never proceeds to a formal grievance_store submission, so HR
            // has visibility into informal attempts that never escalate.
            $table->enum('resolved_informally', ['Yes', 'No']);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts')->onDelete('cascade');
            $table->index(['resort_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('grievance_informal_resolutions');
    }
}
