<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('disciplinary_investigation_parents', function (Blueprint $table) {
            $table->string('signature_img')->nullable()->after('outcome_type');
            $table->string('signature_name')->nullable()->after('signature_img');
            $table->timestamp('signed_at')->nullable()->after('signature_name');
        });

        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->string('resolved_signature_img')->nullable()->after('outcome_type');
            $table->string('resolved_signature_name')->nullable()->after('resolved_signature_img');
            $table->timestamp('resolved_signed_at')->nullable()->after('resolved_signature_name');
        });
    }

    public function down()
    {
        Schema::table('disciplinary_investigation_parents', function (Blueprint $table) {
            $table->dropColumn(['signature_img', 'signature_name', 'signed_at']);
        });

        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->dropColumn(['resolved_signature_img', 'resolved_signature_name', 'resolved_signed_at']);
        });
    }
};
