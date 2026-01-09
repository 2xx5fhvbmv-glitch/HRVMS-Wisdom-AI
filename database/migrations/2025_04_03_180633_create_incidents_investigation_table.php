<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncidentsInvestigationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incidents_investigation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_id');
            $table->enum('police_notified', ['yes', 'no', 'not_required'])->default('no');
            $table->dateTime('police_notified_at')->nullable();
            $table->enum('mdf_notified', ['yes', 'no', 'not_required'])->default('no');
            $table->dateTime('mdf_notified_at')->nullable();
            $table->enum('fire_rescue_notified', ['yes', 'no', 'not_required'])->default('no');
            $table->dateTime('fire_rescue_notified_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expected_resolution_date')->nullable();
            $table->text('investigation_findings')->nullable();
            $table->string('folloup_action')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->string('outcome_type')->nullable();
            $table->string('preventive_measures')->nullable();
            $table->string('action_taken')->nullable();
            $table->boolean('approval')->default(false);
            $table->timestamps();

            $table->foreign('incident_id')->references('id')->on('incidents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incidents_investigation');
    }
}
