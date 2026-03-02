<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_offer_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('ta_document_template_id')->nullable()->after('sent_by');
            $table->longText('generated_html')->nullable()->after('ta_document_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_offer_contracts', function (Blueprint $table) {
            $table->dropColumn(['ta_document_template_id', 'generated_html']);
        });
    }
};