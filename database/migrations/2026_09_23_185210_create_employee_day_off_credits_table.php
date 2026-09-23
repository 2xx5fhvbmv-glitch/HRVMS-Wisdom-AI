<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_day_off_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('emp_id');
            $table->date('week_start_date');
            $table->unsignedInteger('roster_id')->nullable();
            $table->timestamps();

            $table->unique(['emp_id', 'resort_id', 'week_start_date']);
            $table->foreign('emp_id')->references('id')->on('employees');
            $table->foreign('resort_id')->references('id')->on('resorts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_day_off_credits');
    }
};
