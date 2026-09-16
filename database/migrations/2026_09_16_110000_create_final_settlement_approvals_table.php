<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-stage (HR → Finance → GM) approval chain for Full & Final
 * Settlement — §6.2 of the e-signature spec. Shape mirrors the existing
 * PayrollApproval (app/Models/PayrollApproval.php, table
 * payroll_approvals) 3-step chain already used by
 * PayrollController::sendForApproval()/approvePayroll(), plus the
 * signature_img/signature_name/signed_at columns that are the house
 * convention across every other approval table added for this spec
 * (employee_transfers_approval, employee_promotions_approval, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_settlement_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('final_settlement_id');
            $table->unsignedInteger('resort_id');
            $table->unsignedTinyInteger('step_order');
            $table->string('role_title');
            $table->unsignedInteger('approver_id')->nullable();
            $table->string('approver_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signature_img')->nullable();
            $table->string('signature_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->foreign('final_settlement_id')->references('id')->on('final_settlements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_settlement_approvals');
    }
};
