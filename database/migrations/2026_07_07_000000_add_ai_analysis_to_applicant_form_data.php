<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiAnalysisToApplicantFormData extends Migration
{
    public function up()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->text('ai_analysis')->nullable()->after('Scoring');
            $table->timestamp('ai_analysis_generated_at')->nullable()->after('ai_analysis');
        });
    }

    public function down()
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn(['ai_analysis', 'ai_analysis_generated_at']);
        });
    }
}
