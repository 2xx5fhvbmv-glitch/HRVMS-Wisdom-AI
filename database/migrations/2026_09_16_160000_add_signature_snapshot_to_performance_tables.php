<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_pip_plans', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('submitted_by');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });

        Schema::table('employee_pdp_plans', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('submitted_by');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });

        Schema::table('performa_child_cycles', function (Blueprint $table) {
            $table->string('self_signature_img')->nullable()->after('self_review_status');
            $table->string('self_signature_name')->nullable()->after('self_signature_img');
            $table->timestamp('self_signed_at')->nullable()->after('self_signature_name');
            $table->string('manager_signature_img')->nullable()->after('manager_review_status');
            $table->string('manager_signature_name')->nullable()->after('manager_signature_img');
            $table->timestamp('manager_signed_at')->nullable()->after('manager_signature_name');
        });
    }

    public function down()
    {
        Schema::table('employee_pip_plans', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });

        Schema::table('employee_pdp_plans', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });

        Schema::table('performa_child_cycles', function (Blueprint $table) {
            $table->dropColumn(['self_signature_img', 'self_signature_name', 'self_signed_at', 'manager_signature_img', 'manager_signature_name', 'manager_signed_at']);
        });
    }
};
