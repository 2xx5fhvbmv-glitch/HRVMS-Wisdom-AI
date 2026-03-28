<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('payroll_approvals')) {
            Schema::create('payroll_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('payroll_id');
                $table->unsignedInteger('resort_id');
                $table->integer('step_order');
                $table->string('role_title');
                $table->unsignedInteger('approver_id')->nullable();
                $table->string('approver_name')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // Update payroll status enum to include new statuses
        DB::statement("ALTER TABLE payroll MODIFY COLUMN status ENUM('draft','pending_approval','approved','locked') DEFAULT 'draft'");
    }

    public function down()
    {
        Schema::dropIfExists('payroll_approvals');
        DB::statement("ALTER TABLE payroll MODIFY COLUMN status ENUM('draft','locked') DEFAULT 'draft'");
    }
};
