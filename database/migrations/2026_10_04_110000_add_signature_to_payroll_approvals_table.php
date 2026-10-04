<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_approvals', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('approved_at');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_approvals', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });
    }
};
