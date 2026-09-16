<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('incident_employee_statements', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('status');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });

        Schema::table('incidents_witness', function (Blueprint $table) {
            $table->string('witness_signature_img')->nullable()->after('witness_status');
            $table->string('witness_signature_name')->nullable()->after('witness_signature_img');
            $table->timestamp('witness_signed_at')->nullable()->after('witness_signature_name');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('gm_signature_img')->nullable()->after('approval_remarks');
            $table->string('gm_signature_name')->nullable()->after('gm_signature_img');
            $table->timestamp('gm_signed_at')->nullable()->after('gm_signature_name');
        });
    }

    public function down()
    {
        Schema::table('incident_employee_statements', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });

        Schema::table('incidents_witness', function (Blueprint $table) {
            $table->dropColumn(['witness_signature_img', 'witness_signature_name', 'witness_signed_at']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['gm_signature_img', 'gm_signature_name', 'gm_signed_at']);
        });
    }
};
