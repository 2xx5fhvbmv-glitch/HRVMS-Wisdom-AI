<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Which catalog services are eligible for which Benefit Grid grade —
        // a grade can have many services and a service can be shared across
        // many grades, same shape as resort_benefit_grade_level_ranks
        // (grade<->rank pivot) already used by this module.
        Schema::create('benefit_grade_housekeeping_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resort_id');
            $table->unsignedBigInteger('grade_level_id');
            $table->unsignedBigInteger('housekeeping_service_id');
            $table->timestamps();

            $table->unique(['grade_level_id', 'housekeeping_service_id'], 'bghs_grade_service_unique');
            $table->index('resort_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('benefit_grade_housekeeping_services');
    }
};
