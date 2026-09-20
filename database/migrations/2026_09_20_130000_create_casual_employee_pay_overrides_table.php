<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WP3 (D3) — Level 2 of the one salary source for Casual/Intern: an
 * optional per-person override on top of the position rate
 * (casual_position_pay_configs). Resolver rule (Common::casualInternBasicSalary()):
 * custom if a row exists here, else the employee's position rate.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('casual_employee_pay_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->unsignedInteger('employee_id');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->enum('basic_salary_currency', ['USD', 'MVR'])->default('USD');
            $table->timestamps();

            $table->foreign('resort_id')->references('id')->on('resorts');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['resort_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('casual_employee_pay_overrides');
    }
};
