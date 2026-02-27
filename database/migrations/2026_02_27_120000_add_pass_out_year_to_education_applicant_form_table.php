<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_applicant_form', function (Blueprint $table) {
            $table->string('pass_out_year')->nullable()->after('city_educational');
        });
    }

    public function down(): void
    {
        Schema::table('education_applicant_form', function (Blueprint $table) {
            $table->dropColumn('pass_out_year');
        });
    }
};
