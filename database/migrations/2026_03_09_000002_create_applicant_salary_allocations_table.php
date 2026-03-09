<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_salary_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('resort_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('department_id');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('allowances')->nullable(); // [{resort_budget_cost_id, value, currency}]
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();

            $table->index(['applicant_id', 'resort_id'], 'asa_applicant_resort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_salary_allocations');
    }
};
