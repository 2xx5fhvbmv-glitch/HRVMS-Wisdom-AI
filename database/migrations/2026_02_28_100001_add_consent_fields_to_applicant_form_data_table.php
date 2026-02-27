<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->enum('consent_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->date('consent_expiry_date')->nullable();
            $table->string('consent_token')->unique()->nullable();
            $table->timestamp('consent_responded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicant_form_data', function (Blueprint $table) {
            $table->dropColumn(['consent_status', 'consent_expiry_date', 'consent_token', 'consent_responded_at']);
        });
    }
};
