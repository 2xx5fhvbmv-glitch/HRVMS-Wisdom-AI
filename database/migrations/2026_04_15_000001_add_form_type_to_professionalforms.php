<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('professionalforms', function (Blueprint $table) {
            if (!Schema::hasColumn('professionalforms', 'form_type')) {
                $table->string('form_type', 50)->default('ProfessionalForm')->after('FormName');
            }
        });

        // Create PIP assignments table
        if (!Schema::hasTable('employee_pip_plans')) {
            Schema::create('employee_pip_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('resort_id');
                $table->unsignedInteger('employee_id');
                $table->unsignedInteger('position_id')->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('duration', 100)->nullable();
                $table->text('factors')->nullable();
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['resort_id', 'employee_id']);
            });
        }

        // Create PDP assignments table
        if (!Schema::hasTable('employee_pdp_plans')) {
            Schema::create('employee_pdp_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('resort_id');
                $table->unsignedInteger('employee_id');
                $table->unsignedInteger('position_id')->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('duration', 100)->nullable();
                $table->text('factors')->nullable();
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['resort_id', 'employee_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('employee_pdp_plans');
        Schema::dropIfExists('employee_pip_plans');
        Schema::table('professionalforms', function (Blueprint $table) {
            if (Schema::hasColumn('professionalforms', 'form_type')) {
                $table->dropColumn('form_type');
            }
        });
    }
};
